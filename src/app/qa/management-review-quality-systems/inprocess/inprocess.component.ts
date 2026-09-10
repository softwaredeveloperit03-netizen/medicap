import { Component, OnInit } from '@angular/core';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
import { API, FORM_MOM, SOP_REF, loadManagers, participantLabel, stampNow } from '../management-review.utils';

declare let alertify: any;

@Component({
  selector: 'app-mrq-inprocess',
  templateUrl: './inprocess.component.html',
  styleUrls: ['../management-review.shared.css'],
  providers: [DatePipe],
})
export class InprocessComponent implements OnInit {
  formNo = FORM_MOM;
  sopRef = SOP_REF;
  loading = false;
  isView = false;
  results: any[] = [];
  selectedResult: any = null;
  employees: any[] = [];

  momDiscussions: any[] = [];
  actionItems: any[] = [];
  remark = '';
  conclusions = '';
  nextReviewDate = '';
  momPreparedBy = '';

  actionText = '';
  actionResponsible = '';
  actionTargetDate = '';

  constructor(private service: DataAccessService, private datePipe: DatePipe) {}

  ngOnInit(): void {
    this.getInprocessMeetings();
    this.loadEmployees();
  }

  loadEmployees(): void {
    loadManagers(this.service).subscribe(
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

  employeeLabel(emp: any): string {
    return participantLabel(emp);
  }

  getInprocessMeetings(): void {
    this.loading = true;
    this.service.get(`${API}?type=getInprocessMeetings`).subscribe(
      (response: any) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.loading = false;
      }
    );
  }

  view(index: number): void {
    this.selectedResult = JSON.parse(JSON.stringify(this.results[index]));
    const agenda = this.selectedResult.agenda || [];
    this.momDiscussions = agenda.map((a: any) => ({
      agenda: a.agenda,
      detail: a.detail,
      discussion: '',
      decision: '',
    }));
    this.actionItems =
      Array.isArray(this.selectedResult.action_items) && this.selectedResult.action_items.length
        ? this.selectedResult.action_items
        : [];
    this.remark = this.selectedResult.remark || '';
    this.conclusions = this.selectedResult.conclusions || '';
    this.nextReviewDate = this.selectedResult.next_review_date || '';
    this.momPreparedBy = this.selectedResult.mom_prepared_by || '';
    this.isView = true;
  }

  addAttendance(index: number, status: string): void {
    const data = [...(this.selectedResult.participants || [])];
    data[index].attendance = status;
    this.selectedResult.participants = data;
  }

  addActionItem(): void {
    if (!this.actionText.trim()) {
      alertify.error('Please enter action description');
      return;
    }
    if (!this.actionResponsible) {
      alertify.error('Please select responsible person');
      return;
    }
    this.actionItems.push({
      action: this.actionText,
      responsible: this.actionResponsible,
      target_date: this.actionTargetDate,
    });
    this.actionText = '';
    this.actionResponsible = '';
    this.actionTargetDate = '';
  }

  stampMomPreparedBy(): void {
    this.momPreparedBy = stampNow(this.datePipe);
  }

  completeMeeting(): void {
    const missingAttendance = (this.selectedResult.participants || []).some((p: any) => !p.attendance);
    if (missingAttendance) {
      alertify.error('Please mark attendance for all participants');
      return;
    }
    const payload = {
      id: this.selectedResult.id,
      participants: this.selectedResult.participants,
      mom_discussions: this.momDiscussions,
      action_items: this.actionItems,
      remark: this.remark,
      conclusions: this.conclusions,
      next_review_date: this.nextReviewDate,
      mom_prepared_by: this.momPreparedBy || stampNow(this.datePipe),
    };
    this.service.post(`${API}?type=completeMeeting`, JSON.stringify(payload)).subscribe((response: any) => {
      if (response?.status === 'success') {
        alertify.success('Minutes of Meeting saved successfully');
        this.getInprocessMeetings();
        this.isView = false;
      } else {
        alertify.error(response?.status || 'Failed to complete meeting');
      }
    });
  }
}
