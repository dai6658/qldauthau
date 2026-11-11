-- =====================================================================
--  CSDL: Hệ thống Quản lý Đấu thầu & Mua sắm (MySQL 5.7+/MariaDB 10.3+)
-- =====================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS settings, invoices, purchase_orders, acceptances, payments, contracts,
  expenses, investment_items, task_updates, plan_tasks, tender_plans, approvals, workflow_steps,
  reminder_log, notifications, audit_logs, encryption_keys, share_links, document_permissions,
  document_versions, documents, bid_scores, bid_items, bids, tender_invitations, tender_members,
  tender_criteria, tender_items, tenders, tender_templates, supplier_evaluations,
  supplier_capabilities, suppliers, projects, users, role_permissions, permissions, roles,
  departments, companies;

-- ---------------------------------------------------------------------
-- 1. Cơ cấu tổ chức & phân quyền
-- ---------------------------------------------------------------------
CREATE TABLE companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  tax_code VARCHAR(30) NULL,
  address VARCHAR(255) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_dept (company_id, code),
  CONSTRAINT fk_dept_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(150) NOT NULL,
  module VARCHAR(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NULL,
  phone VARCHAR(30) NULL,
  role_id INT NOT NULL,
  company_id INT NULL,
  department_id INT NULL,
  supplier_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user_role (role_id),
  KEY idx_user_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  company_id INT NOT NULL,
  department_id INT NULL,
  investor VARCHAR(255) NULL,
  location VARCHAR(255) NULL,
  description TEXT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  status ENUM('planning','active','completed','suspended') NOT NULL DEFAULT 'planning',
  manager_id INT NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Nhà cung cấp / nhà thầu
-- ---------------------------------------------------------------------
CREATE TABLE suppliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  tax_code VARCHAR(30) NOT NULL UNIQUE,
  address VARCHAR(255) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(150) NULL,
  website VARCHAR(150) NULL,
  representative VARCHAR(150) NULL,
  business_field VARCHAR(255) NULL,
  established_year INT NULL,
  charter_capital DECIMAL(18,0) NULL,
  annual_revenue DECIMAL(18,0) NULL,
  employees INT NULL,
  bank_account VARCHAR(50) NULL,
  bank_name VARCHAR(150) NULL,
  status ENUM('pending','approved','suspended','blacklisted') NOT NULL DEFAULT 'pending',
  status_note VARCHAR(255) NULL,
  rating DECIMAL(4,2) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_capabilities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  cap_type ENUM('experience','finance','personnel','equipment','certificate') NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  value_amount DECIMAL(18,0) NULL,
  year INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cap_sup FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE supplier_evaluations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier_id INT NOT NULL,
  contract_id INT NULL,
  quality_score TINYINT NOT NULL,
  schedule_score TINYINT NOT NULL,
  price_score TINYINT NOT NULL,
  service_score TINYINT NOT NULL,
  overall DECIMAL(4,2) NOT NULL,
  comment TEXT NULL,
  evaluated_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_eval_sup FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Hồ sơ mời thầu (HSMT) & hồ sơ dự thầu (HSDT)
-- ---------------------------------------------------------------------
CREATE TABLE tender_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  category ENUM('goods','construction','consulting','non_consulting') NOT NULL DEFAULT 'goods',
  method ENUM('open','limited','direct','competitive') NOT NULL DEFAULT 'open',
  evaluation_method ENUM('qcs','lowest_price','quality') NOT NULL DEFAULT 'qcs',
  tech_weight INT NOT NULL DEFAULT 70,
  price_weight INT NOT NULL DEFAULT 30,
  pass_tech_score INT NOT NULL DEFAULT 70,
  content MEDIUMTEXT NULL,
  criteria_json TEXT NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tenders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  project_id INT NOT NULL,
  investment_item_id INT NULL,
  template_id INT NULL,
  title VARCHAR(255) NOT NULL,
  category ENUM('goods','construction','consulting','non_consulting') NOT NULL DEFAULT 'goods',
  method ENUM('open','limited','direct','competitive') NOT NULL DEFAULT 'open',
  evaluation_method ENUM('qcs','lowest_price','quality') NOT NULL DEFAULT 'qcs',
  tech_weight INT NOT NULL DEFAULT 70,
  price_weight INT NOT NULL DEFAULT 30,
  pass_tech_score INT NOT NULL DEFAULT 70,
  budget DECIMAL(18,0) NOT NULL DEFAULT 0,
  bid_security DECIMAL(18,0) NULL,
  description TEXT NULL,
  content MEDIUMTEXT NULL,
  status ENUM('draft','pending_approval','approved','published','closed','evaluating','awarded','cancelled') NOT NULL DEFAULT 'draft',
  publish_at DATETIME NULL,
  close_at DATETIME NULL,
  opening_at DATETIME NULL,
  awarded_bid_id INT NULL,
  cancel_reason VARCHAR(255) NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_tender_status (status),
  KEY idx_tender_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tender_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tender_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  specs TEXT NULL,
  unit VARCHAR(30) NULL,
  quantity DECIMAL(14,2) NOT NULL DEFAULT 1,
  estimated_price DECIMAL(18,0) NOT NULL DEFAULT 0,
  CONSTRAINT fk_item_tender FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tender_criteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tender_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(500) NULL,
  max_score INT NOT NULL DEFAULT 10,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_crit_tender FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tender_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tender_id INT NOT NULL,
  user_id INT NOT NULL,
  access ENUM('view','edit','evaluate') NOT NULL DEFAULT 'view',
  UNIQUE KEY uq_member (tender_id, user_id),
  CONSTRAINT fk_mem_tender FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tender_invitations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tender_id INT NOT NULL,
  supplier_id INT NOT NULL,
  invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inv (tender_id, supplier_id),
  CONSTRAINT fk_inv_tender FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bids (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  tender_id INT NOT NULL,
  supplier_id INT NOT NULL,
  total_price DECIMAL(18,0) NOT NULL DEFAULT 0,
  validity_days INT NULL,
  delivery_days INT NULL,
  warranty_months INT NULL,
  technical_proposal TEXT NULL,
  status ENUM('draft','submitted','valid','invalid','awarded','rejected','withdrawn') NOT NULL DEFAULT 'draft',
  submitted_at DATETIME NULL,
  opened_at DATETIME NULL,
  tech_score DECIMAL(6,2) NULL,
  price_score DECIMAL(6,2) NULL,
  final_score DECIMAL(6,2) NULL,
  passed TINYINT(1) NULL,
  rank_no INT NULL,
  review_note VARCHAR(500) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_bid (tender_id, supplier_id),
  CONSTRAINT fk_bid_tender FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bid_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bid_id INT NOT NULL,
  tender_item_id INT NOT NULL,
  unit_price DECIMAL(18,0) NOT NULL DEFAULT 0,
  amount DECIMAL(18,0) NOT NULL DEFAULT 0,
  note VARCHAR(255) NULL,
  UNIQUE KEY uq_bid_item (bid_id, tender_item_id),
  CONSTRAINT fk_bi_bid FOREIGN KEY (bid_id) REFERENCES bids(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bid_scores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bid_id INT NOT NULL,
  criterion_id INT NOT NULL,
  evaluator_id INT NOT NULL,
  score DECIMAL(6,2) NOT NULL DEFAULT 0,
  comment VARCHAR(500) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_score (bid_id, criterion_id, evaluator_id),
  CONSTRAINT fk_bs_bid FOREIGN KEY (bid_id) REFERENCES bids(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Kho tài liệu tập trung (phiên bản, phân quyền, chia sẻ, mã hóa)
-- ---------------------------------------------------------------------
CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  category VARCHAR(30) NOT NULL DEFAULT 'other',
  description TEXT NULL,
  tags VARCHAR(255) NULL,
  entity_type VARCHAR(30) NULL,
  entity_id INT NULL,
  company_id INT NULL,
  department_id INT NULL,
  project_id INT NULL,
  confidentiality ENUM('public','internal','confidential') NOT NULL DEFAULT 'internal',
  current_version INT NOT NULL DEFAULT 1,
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  KEY idx_doc_entity (entity_type, entity_id),
  KEY idx_doc_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE document_versions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  document_id INT NOT NULL,
  version_no INT NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  extension VARCHAR(10) NOT NULL,
  mime VARCHAR(120) NULL,
  size_bytes BIGINT NOT NULL DEFAULT 0,
  sha256 CHAR(64) NOT NULL,
  storage_path VARCHAR(255) NOT NULL,
  iv VARCHAR(64) NOT NULL,
  tag VARCHAR(64) NOT NULL,
  key_id INT NOT NULL,
  content_text MEDIUMTEXT NULL,
  change_note VARCHAR(500) NULL,
  uploaded_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_doc_ver (document_id, version_no),
  CONSTRAINT fk_ver_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE document_permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  document_id INT NOT NULL,
  user_id INT NULL,
  role_id INT NULL,
  department_id INT NULL,
  can_view TINYINT(1) NOT NULL DEFAULT 1,
  can_download TINYINT(1) NOT NULL DEFAULT 0,
  can_edit TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_dp_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE share_links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  document_id INT NOT NULL,
  token CHAR(48) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  expires_at DATETIME NOT NULL,
  max_downloads INT NULL,
  download_count INT NOT NULL DEFAULT 0,
  is_revoked TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sl_doc FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE encryption_keys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  key_type ENUM('system','customer') NOT NULL DEFAULT 'system',
  wrapped_key TEXT NOT NULL,
  fingerprint VARCHAR(32) NOT NULL,
  status ENUM('active','inactive','revoked') NOT NULL DEFAULT 'inactive',
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Nhật ký kiểm toán (bất biến, chuỗi băm), thông báo, quy trình phê duyệt
-- ---------------------------------------------------------------------
CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  username VARCHAR(60) NULL,
  action VARCHAR(60) NOT NULL,
  entity_type VARCHAR(30) NULL,
  entity_id INT NULL,
  details TEXT NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  created_at DATETIME NOT NULL,
  prev_hash CHAR(64) NOT NULL DEFAULT '',
  hash CHAR(64) NOT NULL,
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_time (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  message VARCHAR(1000) NULL,
  link VARCHAR(255) NULL,
  type VARCHAR(20) NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_noti_user (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reminder_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(40) NOT NULL,
  entity_id INT NOT NULL,
  user_id INT NOT NULL,
  sent_on DATE NOT NULL,
  UNIQUE KEY uq_rem (kind, entity_id, user_id, sent_on)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE workflow_steps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(30) NOT NULL,
  step_no INT NOT NULL,
  name VARCHAR(150) NOT NULL,
  role_id INT NOT NULL,
  UNIQUE KEY uq_step (entity_type, step_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(30) NOT NULL,
  entity_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  round_no INT NOT NULL DEFAULT 1,
  step_no INT NOT NULL,
  step_name VARCHAR(150) NOT NULL,
  role_id INT NOT NULL,
  status ENUM('waiting','pending','approved','rejected','cancelled') NOT NULL DEFAULT 'waiting',
  requested_by INT NULL,
  acted_by INT NULL,
  comment VARCHAR(500) NULL,
  acted_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_appr_entity (entity_type, entity_id),
  KEY idx_appr_status (status, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Kế hoạch đấu thầu & tiến độ
-- ---------------------------------------------------------------------
CREATE TABLE tender_plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  project_id INT NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  status ENUM('draft','pending_approval','approved','in_progress','completed') NOT NULL DEFAULT 'draft',
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plan_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  plan_id INT NOT NULL,
  tender_id INT NULL,
  stage VARCHAR(100) NULL,
  name VARCHAR(255) NOT NULL,
  assignee_id INT NULL,
  department_id INT NULL,
  start_date DATE NULL,
  due_date DATE NULL,
  progress TINYINT NOT NULL DEFAULT 0,
  status ENUM('todo','in_progress','done','blocked') NOT NULL DEFAULT 'todo',
  note VARCHAR(500) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  completed_at DATETIME NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  CONSTRAINT fk_task_plan FOREIGN KEY (plan_id) REFERENCES tender_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE task_updates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  task_id INT NOT NULL,
  user_id INT NULL,
  progress TINYINT NOT NULL,
  status VARCHAR(20) NOT NULL,
  note VARCHAR(500) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tu_task FOREIGN KEY (task_id) REFERENCES plan_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Tổng mức đầu tư (TMĐT) & chi phí
-- ---------------------------------------------------------------------
CREATE TABLE investment_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  category ENUM('construction','equipment','land','management','consulting','other','contingency') NOT NULL,
  name VARCHAR(255) NOT NULL,
  estimated_amount DECIMAL(18,0) NOT NULL DEFAULT 0,
  approved_amount DECIMAL(18,0) NOT NULL DEFAULT 0,
  note VARCHAR(500) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_inv_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  investment_item_id INT NULL,
  contract_id INT NULL,
  payment_id INT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(18,0) NOT NULL,
  expense_date DATE NOT NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_exp_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Hợp đồng, thanh toán/giải ngân, nghiệm thu, đơn hàng, hóa đơn
-- ---------------------------------------------------------------------
CREATE TABLE contracts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  tender_id INT NULL,
  bid_id INT NULL,
  supplier_id INT NOT NULL,
  project_id INT NOT NULL,
  value DECIMAL(18,0) NOT NULL DEFAULT 0,
  sign_date DATE NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  payment_terms TEXT NULL,
  status ENUM('draft','pending_approval','active','completed','terminated') NOT NULL DEFAULT 'draft',
  settled_amount DECIMAL(18,0) NULL,
  settled_at DATETIME NULL,
  settlement_note VARCHAR(500) NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contract_id INT NOT NULL,
  milestone VARCHAR(255) NOT NULL,
  amount DECIMAL(18,0) NOT NULL,
  due_date DATE NULL,
  status ENUM('planned','pending_approval','approved','paid','rejected') NOT NULL DEFAULT 'planned',
  invoice_id INT NULL,
  acceptance_id INT NULL,
  paid_amount DECIMAL(18,0) NULL,
  paid_at DATE NULL,
  note VARCHAR(500) NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pay_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE acceptances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contract_id INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  accept_date DATE NOT NULL,
  result ENUM('passed','conditional','failed') NOT NULL DEFAULT 'passed',
  note VARCHAR(500) NULL,
  document_id INT NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_acc_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE purchase_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  contract_id INT NOT NULL,
  order_date DATE NOT NULL,
  delivery_date DATE NULL,
  amount DECIMAL(18,0) NOT NULL DEFAULT 0,
  description TEXT NULL,
  status ENUM('sent','confirmed','delivered','cancelled') NOT NULL DEFAULT 'sent',
  supplier_note VARCHAR(500) NULL,
  confirmed_at DATETIME NULL,
  created_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_po_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  contract_id INT NOT NULL,
  supplier_id INT NOT NULL,
  invoice_no VARCHAR(50) NOT NULL,
  invoice_date DATE NOT NULL,
  amount DECIMAL(18,0) NOT NULL,
  vat_amount DECIMAL(18,0) NOT NULL DEFAULT 0,
  status ENUM('submitted','verified','rejected','paid') NOT NULL DEFAULT 'submitted',
  document_id INT NULL,
  note VARCHAR(500) NULL,
  review_note VARCHAR(500) NULL,
  submitted_by INT NULL,
  submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  reviewed_by INT NULL,
  reviewed_at DATETIME NULL,
  UNIQUE KEY uq_invoice (supplier_id, invoice_no),
  CONSTRAINT fk_invc_contract FOREIGN KEY (contract_id) REFERENCES contracts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
  skey VARCHAR(100) PRIMARY KEY,
  svalue TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Trigger: nhật ký kiểm toán là BẤT BIẾN (không cho sửa / xóa)
-- ---------------------------------------------------------------------
DROP TRIGGER IF EXISTS trg_audit_no_update;
DROP TRIGGER IF EXISTS trg_audit_no_delete;

DELIMITER $$
CREATE TRIGGER trg_audit_no_update BEFORE UPDATE ON audit_logs
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is immutable (UPDATE not allowed)';
END$$

CREATE TRIGGER trg_audit_no_delete BEFORE DELETE ON audit_logs
FOR EACH ROW
BEGIN
  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'audit_logs is immutable (DELETE not allowed)';
END$$
DELIMITER ;
