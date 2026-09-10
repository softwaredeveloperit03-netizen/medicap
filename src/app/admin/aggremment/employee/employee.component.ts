import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-employee',
  templateUrl: './employee.component.html',
  styleUrls: ['./employee.component.css'],
})
export class EmployeeComponent implements OnInit {
  isNew = false;
  entries: any[] = [];
  selectedEntry: any;
  isApprover: any;
  isChecker: any;
  isView = false;

  file_agreement: File | undefined;
  agreement: string;

  /** Company dropdown from localStorage all_plants */
  plants: { plant_id: string; display_name: string }[] = [];

  /** Employee dropdown from marketing/client.php getClientsDetails (client.LglNm) */
  clients: { LglNm: string }[] = [];

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights: any;
  loggedInDept: string;

  constructor(private service: DataAccessService, private router: Router) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.loadPlantsFromStorage();
    this.getClientsDetails();
    this.getAgreementDetails();
    this.get_rights();
  }

  loadPlantsFromStorage() {
    try {
      const raw = localStorage.getItem('all_plants');
      const parsed = raw ? JSON.parse(raw) : [];
      this.plants = Array.isArray(parsed) ? parsed : [];
    } catch {
      this.plants = [];
    }
  }

  getClientsDetails() {
    this.service.get('marketing/client.php?type=getClientsDetails').subscribe({
      next: (response: any) => {
        this.clients = Array.isArray(response) ? response : [];
      },
      error: () => {
        this.clients = [];
      },
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
      .subscribe((response: any) => {
        this.rights = response;
        if (this.rights && this.rights[0]) {
          this.isuser = this.rights[0].isuser;
          this.ischecker = this.rights[0].ischecker;
          this.isapprover = this.rights[0].isapprover;
          this.qms_approver = this.rights[0].qms_approver;
          this.dept_head = this.rights[0].dept_head;
          this.isauditor = this.rights[0].isauditor;
          this.plant_head = this.rights[0].plant_head;
          this.shift_allocator = this.rights[0].shift_allocator;
        }
      });
  }

  open(url: string) {
    window.open(url, '_blank');
  }

  viewEntry(index: number) {
    this.selectedEntry = this.entries[index];
    this.agreement = this.service.url + this.selectedEntry['file_agreement'];
    this.isView = true;
  }

  onFileChange($event: Event, name: string) {
    const input = $event.target as HTMLInputElement;
    if (name === 'agreement' && input.files && input.files.length > 0) {
      this.file_agreement = input.files[0];
    }
  }

  saveForm(data: any) {
    const formData = new FormData();

    formData.append('agreement_type', data.value.agreement_type); // NEW
    formData.append('agreement_title', data.value.agreement_title);
    formData.append('company_name', data.value.company_name);
    formData.append('employee_name', data.value.employee_name);
    formData.append('description', data.value.description);
    formData.append('agreement_date', data.value.agreement_date);
    formData.append('valid_date', data.value.valid_date); // NEW
    formData.append('legal_form', data.value.legal_form);

    if (this.file_agreement) {
      formData.append('file_agreement', this.file_agreement, this.file_agreement.name);
    }

    this.service.post('admin.php?type=saveEmployeeAgreement', formData).subscribe(
      (response: any) => {
        const result = JSON.parse(JSON.stringify(response));
        if (result.status === 'success') {
          data.resetForm();
          this.getAgreementDetails();
          this.isNew = false;
          this.file_agreement = undefined;
          alert('Saved Successfully');
        } else {
          alert('An error has occurred, please try again');
        }
      },
      (error: any) => {
        alert('An error has occurred, http status:' + (error?.status ?? ''));
      }
    );
  }

  getAgreementDetails() {
    this.service.get('admin.php?type=getEmployeeAgreement').subscribe((response: any) => {
      this.entries = Array.isArray(response) ? response : [];
    });
  }

  close() {
    this.router.navigate(['/admin/aggremment']);
  }
}