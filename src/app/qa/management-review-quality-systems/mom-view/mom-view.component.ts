import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { API, FORM_MOM, SOP_REF } from '../management-review.utils';

@Component({
  selector: 'app-mrq-mom-view',
  templateUrl: './mom-view.component.html',
  styleUrls: ['../management-review.shared.css'],
})
export class MomViewComponent implements OnInit {
  formNo = FORM_MOM;
  sopRef = SOP_REF;
  loading = false;
  results: any[] = [];
  selectedResult: any = null;
  isView = false;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.loadMom();
  }

  loadMom(): void {
    this.loading = true;
    const empId = localStorage.getItem('emp_id') || '';
    this.service.get(`${API}?type=getMyMomMeetings&emp_id=${encodeURIComponent(empId)}`).subscribe(
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
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  downloadMomPdf(id: number): void {
    this.service.open(`${API}?type=downloadMomPdf&id=${id}`);
  }
}
