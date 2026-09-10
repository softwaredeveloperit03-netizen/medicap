import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { API } from '../management-review.utils';

declare let alertify: any;

@Component({
  selector: 'app-mrq-start',
  templateUrl: './start.component.html',
  styleUrls: ['../management-review.shared.css'],
})
export class StartComponent implements OnInit {
  isView = false;
  results: any[] = [];
  loading = false;
  selectedResult: any = null;

  constructor(private service: DataAccessService) {}

  ngOnInit(): void {
    this.getPendingMeetings();
  }

  getPendingMeetings(): void {
    this.loading = true;
    this.service.get(`${API}?type=getPendingMeetings`).subscribe(
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

  startMeeting(): void {
    this.service.get(`${API}?type=startMeeting&id=${this.selectedResult.id}`).subscribe((response: any) => {
      if (response?.status === 'success') {
        alertify.success('Meeting started successfully');
        this.getPendingMeetings();
        this.isView = false;
      } else {
        alertify.error(response?.status || 'Failed to start meeting');
      }
    });
  }
}
