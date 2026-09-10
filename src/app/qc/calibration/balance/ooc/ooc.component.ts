import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-ooc',
  templateUrl: './ooc.component.html',
  styleUrls: ['./ooc.component.css'],
})
export class OocComponent implements OnInit {
  results: any[] = [];
  selectedResult: any = {};
  isView = false;
  isNewMode = false;
  parametersList: any[] = [];
  balances: any[] = [];

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit(): void {
    this.get_rights();
    this.getPendingOOC();
  }

  getPendingOOC() {
    this.service.get('qc/calibration.php?type=getPendingOOC').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  view(index: number) {
    this.isNewMode = false;
    this.selectedResult = { ...(this.results[index] || {}) };
    this.parametersList = [];
    this.isView = true;
  }

  openNew() {
    this.isNewMode = true;
    this.parametersList = [];
    this.selectedResult = {
      initiate_by: localStorage.getItem('emp_id') || '',
      initiate_date: new Date().toISOString().substring(0, 10),
      department: localStorage.getItem('department') || '',
      calibration_date: new Date().toISOString().substring(0, 10),
    };
    this.loadBalances();
    this.isView = true;
  }

  loadBalances() {
    this.service
      .get(
        'common.php?type=getbal_id&department1=' +
          encodeURIComponent('Quality Control')
      )
      .subscribe((response: any) => {
        this.balances = Array.isArray(response) ? response : [];
      });
  }

  onEquipmentSelect(equipmentCode: string) {
    const row = (this.balances || []).find(
      (item) => String(item.equipment_code) === String(equipmentCode)
    );
    if (!row) {
      return;
    }
    this.selectedResult = {
      ...this.selectedResult,
      equipment_code: row.equipment_code,
      equipment_name: row.equipment_name,
      make: row.make,
      code_no: row.serial_no,
      location: row.location || row.section,
      stage: row.stage || row.section,
      department: row.department || this.selectedResult.department,
    };
  }

  closeForm() {
    this.isView = false;
    this.isNewMode = false;
    this.parametersList = [];
    this.selectedResult = {};
  }

  add(data) {
    if (!data.valid) {
      alertify.error('All Fields required');
      return;
    }
    this.parametersList = [...this.parametersList, data.value];
    data.resetForm();
  }

  del(index: number) {
    this.parametersList.splice(index, 1);
  }

  save(data) {
    if (!data.valid) {
      alertify.error('All Fields Are Required !!');
      return;
    }
    if (this.parametersList.length === 0) {
      alertify.error('Add at least one calibration parameter');
      return;
    }
    if (!this.selectedResult.equipment_code) {
      alertify.error('Equipment is required');
      return;
    }

    const temp = { ...data.value };
    temp.calibration_parameter = this.parametersList;
    temp.id = this.selectedResult.id;
    temp.equipment_code = this.selectedResult.equipment_code;
    temp.is_new = this.isNewMode;

    this.service
      .post('qc/calibration.php?type=saveOOC', JSON.stringify(temp))
      .subscribe((response) => {
        if (response['status'] === 'success') {
          this.closeForm();
          this.getPendingOOC();
          alertify.success('Records Save Successfully !!!');
        } else {
          alertify.error(response['message'] || 'Error to save records !!');
        }
      });
  }

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
}
