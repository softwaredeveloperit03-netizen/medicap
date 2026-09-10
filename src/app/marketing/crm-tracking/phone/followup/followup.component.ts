import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-phone-followup',
  templateUrl: './followup.component.html',
  styleUrls: ['./followup.component.css']
})
export class PhoneFollowupComponent implements OnInit {

  calls: any[] = [];
  loading = false;
  isView = false;
  selectedCall: any = {};
  followupDate: string = '';
  followupTime: string = '';
  followupNotes: string = '';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getFollowupCalls();
  }

  getFollowupCalls() {
    this.loading = true;
    this.service.get('marketing/crm-tracking.php?type=getFollowupPhoneCalls').subscribe((response: any) => {
      this.calls = response || [];
      this.loading = false;
    }, error => {
      console.error('Error fetching followup calls:', error);
      alertify.error('Error fetching followup calls');
      this.loading = false;
    });
  }

  view(index: number) {
    this.selectedCall = this.calls[index];
    this.followupDate = this.selectedCall.followup_date || '';
    this.followupTime = this.selectedCall.followup_time || '';
    this.followupNotes = '';
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedCall = {};
  }

  saveFollowup() {
    if (!this.followupDate || !this.followupTime) {
      alertify.error('Please select followup date and time');
      return;
    }

    const data = {
      id: this.selectedCall.id,
      followup_date: this.followupDate,
      followup_time: this.followupTime,
      followup_notes: this.followupNotes
    };

    this.service.post('marketing/crm-tracking/crm-tracking.php?type=updatePhoneFollowup', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Followup updated successfully');
        this.closeView();
        this.getFollowupCalls();
      } else {
        alertify.error(response.message || 'Error updating followup');
      }
    }, error => {
      console.error('Error updating followup:', error);
      alertify.error('Error updating followup');
    });
  }

  refresh() {
    this.getFollowupCalls();
  }
}


