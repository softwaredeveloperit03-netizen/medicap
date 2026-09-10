import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-existing-emp',
  templateUrl: './existing-emp.component.html',
  styleUrls: ['./existing-emp.component.css']
})
export class ExistingEmpComponent implements OnInit {
  employees: any[] = [];
  employeeOptions: any[] = [];
  designationMaster: any[] = [];
  designationResponsibilities: string[] = [];

  isView = false;
  isNew = false;
  selectedResult: any = {};
  selectedEmpId = '';

  prime = '';
  second = '';
  qms = '';

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getEmployees();
    this.getDesignationMaster();
  }

  getEmployees(): void {
    this.service.get('qa/job.php?type=getDataJOB').subscribe((response: any) => {
      this.employees = Array.isArray(response) ? response : [];
      this.loadEmployeeOptions();
    });
  }

  getDesignationMaster(): void {
    this.service.get('hr/designation.php?type=getDesignations').subscribe((response: any) => {
      this.designationMaster = Array.isArray(response) ? response : [];
      if (this.selectedResult?.designation) {
        this.loadDesignationResponsibilities(this.selectedResult.designation, this.selectedResult.department);
      }
    });
  }

  private loadEmployeeOptions(): void {
    this.service.get('hr/employee.php?type=getEmployeesList').subscribe((response: any) => {
      const allEmployees = Array.isArray(response) ? response : [];
      const existingIds = new Set((this.employees || []).map((e) => String(e.emp_id)));
      this.employeeOptions = allEmployees.filter((e) => !existingIds.has(String(e.emp_id)));
    });
  }

  view(index: number): void {
    this.selectedResult = { ...this.employees[index] };
    this.isView = true;
    this.isNew = false;
  }

  openNewForm(): void {
    this.isNew = true;
    this.isView = false;
    this.selectedResult = {
      emp_id: '',
      firstname: '',
      lastname: '',
      emp_name: '',
      department: '',
      designation: '',
      Primary_responsibilities: [],
      Secondary_responsibilities: [],
      QMS_responsibilities: [],
    };
    this.selectedEmpId = '';
    this.prime = '';
    this.second = '';
    this.qms = '';
    this.designationResponsibilities = [];
    this.loadEmployeeOptions();
  }

  closePanels(): void {
    this.isView = false;
    this.isNew = false;
    this.selectedResult = {};
    this.selectedEmpId = '';
  }

  onEmployeeSelect(empId: string): void {
    const emp = this.employeeOptions.find((e) => String(e.emp_id) === String(empId));
    if (!emp) {
      return;
    }
    this.selectedResult = {
      emp_id: emp.emp_id,
      firstname: emp.firstname || '',
      lastname: emp.lastname || '',
      emp_name: ((emp.firstname || '') + ' ' + (emp.lastname || '')).trim() || emp.emp_name || '',
      department: emp.department || '',
      designation: emp.designation || '',
      Primary_responsibilities: [],
      Secondary_responsibilities: [],
      QMS_responsibilities: [],
    };
    this.loadDesignationResponsibilities(emp.designation, emp.department);
    this.prime = '';
    this.second = '';
    this.qms = '';
  }

  loadDesignationResponsibilities(designation: string, department?: string): void {
    this.designationResponsibilities = [];
    if (!designation || !this.designationMaster?.length) {
      return;
    }
    const dept = (department || '').trim().toLowerCase();
    const desig = (designation || '').trim().toLowerCase();
    const match =
      this.designationMaster.find((d) => {
        const dDesig = String(d.designation || '').trim().toLowerCase();
        const dDept = String(d.department_name || d.dept_name || d.department || '').trim().toLowerCase();
        return dDesig === desig && (!dept || !dDept || dDept === dept);
      }) ||
      this.designationMaster.find((d) => String(d.designation || '').trim().toLowerCase() === desig);

    if (match?.responsibilities && Array.isArray(match.responsibilities)) {
      this.designationResponsibilities = match.responsibilities
        .map((r: any) => (typeof r === 'string' ? r : r?.responsibility))
        .filter((r: string) => !!String(r || '').trim());
    }
  }

  private ensureRespList(key: string): any[] {
    if (!Array.isArray(this.selectedResult[key])) {
      this.selectedResult[key] = [];
    }
    return this.selectedResult[key];
  }

  private addResponsibility(value: string, listKey: string, clearField: 'prime' | 'second' | 'qms'): void {
    const text = String(value || '').trim();
    if (!text) {
      alertify.error('Please enter or select a responsibility');
      return;
    }
    const list = this.ensureRespList(listKey);
    const exists = list.some((item: any) => String(item?.responsibility || '').trim().toLowerCase() === text.toLowerCase());
    if (exists) {
      alertify.error('This responsibility is already added');
      return;
    }
    list.push({ responsibility: text });
    this[clearField] = '';
  }

  addPrimary(): void {
    this.addResponsibility(this.prime, 'Primary_responsibilities', 'prime');
  }

  addSecondary(): void {
    this.addResponsibility(this.second, 'Secondary_responsibilities', 'second');
  }

  addQms(): void {
    this.addResponsibility(this.qms, 'QMS_responsibilities', 'qms');
  }

  delPrimary(index: number): void {
    this.selectedResult.Primary_responsibilities.splice(index, 1);
  }

  delSecondary(index: number): void {
    this.selectedResult.Secondary_responsibilities.splice(index, 1);
  }

  delQms(index: number): void {
    this.selectedResult.QMS_responsibilities.splice(index, 1);
  }

  onSubmit(): void {
    if (!this.selectedResult?.emp_id) {
      alertify.error('Please select an employee');
      return;
    }

    const primary = this.selectedResult.Primary_responsibilities || [];
    const secondary = this.selectedResult.Secondary_responsibilities || [];
    const qms = this.selectedResult.QMS_responsibilities || [];

    if (!primary.length && !secondary.length && !qms.length) {
      alertify.error('Please add at least one responsibility');
      return;
    }

    const payload = { ...this.selectedResult };
    if (!payload.firstname && payload.emp_name) {
      const parts = String(payload.emp_name).trim().split(/\s+/);
      payload.firstname = parts[0] || '';
      payload.lastname = parts.slice(1).join(' ') || '';
    }

    this.service.post('qa/job.php?type=savejob_responsibilities', JSON.stringify(payload)).subscribe((response: any) => {
      if (response['status'] === 'success') {
        alertify.success('Job responsibility saved successfully');
        this.closePanels();
        this.getEmployees();
      } else {
        alertify.error(response['status'] || 'Failed to save, please try again');
      }
    });
  }

  downloadLog(): void {
    this.service.open('qa/job.php?type=downloadJobResponsibilityLog');
  }

  downloadPDF(): void {
    if (!this.selectedResult?.emp_id) {
      alertify.error('No employee selected');
      return;
    }
    this.service.open(
      'qa/job.php?type=downloadJobResponsibility&record_emp_id=' + encodeURIComponent(this.selectedResult.emp_id)
    );
  }
}
