import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  AGENDA_OPTIONS,
  API,
  FORM_ANNOUNCEMENT,
  REVIEW_PERIODS,
  SOP_REF,
  loadManagers,
  participantLabel,
} from '../management-review.utils';

declare let alertify: any;

@Component({
  selector: 'app-mrq-announcement',
  templateUrl: './announcement.component.html',
  styleUrls: ['../management-review.shared.css'],
})
export class AnnouncementComponent implements OnInit {
  formNo = FORM_ANNOUNCEMENT;
  sopRef = SOP_REF;
  revisionNo = '00';
  effectiveDate = '2025-04-16';
  reviewPeriods = REVIEW_PERIODS;
  agendaOptions = AGENDA_OPTIONS;

  departments: any[] = [];
  employees: any[] = [];
  selectedEmp: any = null;
  participants: any[] = [];

  agenda = '';
  detail = '';
  agendas: any[] = [];

  constructor(private service: DataAccessService, private router: Router) {}

  selectedEmpId = '';

  ngOnInit(): void {
    this.getDepartments();
    this.getParticipants();
  }

  getDepartments(): void {
    this.service.get('common.php?type=getDepartments').subscribe((response: any) => {
      this.departments = Array.isArray(response) ? response : [];
    });
  }

  getParticipants(): void {
    loadManagers(this.service).subscribe(
      (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        if (!this.employees.length) {
          this.loadEmployeesFallback();
        }
      },
      () => this.loadEmployeesFallback()
    );
  }

  loadEmployeesFallback(): void {
    this.service.get('employee.php?type=getEmployees').subscribe(
      (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        if (!this.employees.length) {
          this.loadEmployeesFallback2();
        }
      },
      () => this.loadEmployeesFallback2()
    );
  }

  loadEmployeesFallback2(): void {
    this.service.get('hrDepartment.php?type=getEmployeesByDepartment').subscribe(
      (response: any) => {
        this.employees = Array.isArray(response) ? response : [];
        if (!this.employees.length) {
          this.service.get('common.php?type=getEmployees').subscribe((res: any) => {
            this.employees = Array.isArray(res) ? res : [];
          });
        }
      },
      () => {
        this.service.get('common.php?type=getEmployees').subscribe((res: any) => {
          this.employees = Array.isArray(res) ? res : [];
        });
      }
    );
  }

  onParticipantChange(): void {
    const emp = this.employees.find((e) => String(e.emp_id) === String(this.selectedEmpId));
    this.selectedEmp = emp ? { ...emp } : null;
  }

  addParticipant(): void {
    if (!this.selectedEmp) {
      alertify.error('Please select a participant');
      return;
    }
    const already = this.participants.some((p) => String(p.emp_id) === String(this.selectedEmp.emp_id));
    if (already) {
      alertify.error('Participant already added');
      return;
    }
    this.participants.push(this.selectedEmp);
    this.employees = this.employees.filter((e) => String(e.emp_id) !== String(this.selectedEmp.emp_id));
    this.selectedEmp = null;
    this.selectedEmpId = '';
  }

  addAgenda(): void {
    if (!this.agenda || !this.detail) return;
    this.agendas.push({ agenda: this.agenda, detail: this.detail });
    this.agenda = '';
    this.detail = '';
  }

  participantDisplay(p: any): string {
    return participantLabel(p);
  }

  save(data: any): void {
    if (!this.participants.length || !this.agendas.length) {
      alertify.error('Please add at least one participant and one agenda item');
      return;
    }
    const temp = { ...data.value, participants: this.participants, agendas: this.agendas };
    this.service.post(`${API}?type=saveAgenda`, JSON.stringify(temp)).subscribe((response: any) => {
      if (response?.status === 'success') {
        alertify.success('Meeting announcement saved successfully');
        this.router.navigate(['/qa/management-review-quality-systems']);
      } else {
        alertify.error(response?.status || 'Failed to save');
      }
    });
  }
}
