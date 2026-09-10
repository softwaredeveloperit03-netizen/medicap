import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';
declare let alertify: any;

@Component({
  selector: 'app-email-followup',
  templateUrl: './followup.component.html',
  styleUrls: ['./followup.component.css']
})
export class EmailFollowupComponent implements OnInit {

  emails: any[] = [];
  loading = false;
  isView = false;
  selectedEmail: any = {};
  followupDate: string = '';
  followupTime: string = '';
  followupNotes: string = '';

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute,
    private deptNav: DeptNavigationService
  ) { }

  closePage(): void {
    this.deptNav.goBack(this.route, '/marketing/crmTracking');
  }

  ngOnInit(): void {
    this.getFollowupEmails();
  }

  getFollowupEmails() {
    this.loading = true;
    this.service.get('marketing/crm-tracking.php?type=getFollowupEmails').subscribe((response: any) => {
      this.emails = response || [];
      this.loading = false;
    }, error => {
      console.error('Error fetching followup emails:', error);
      alertify.error('Error fetching followup emails');
      this.loading = false;
    });
  }

  view(index: number) {
    this.selectedEmail = this.emails[index];
    this.followupDate = this.selectedEmail.followup_date || '';
    this.followupTime = this.selectedEmail.followup_time || '';
    this.followupNotes = '';
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedEmail = {};
  }

  saveFollowup() {
    if (!this.followupDate || !this.followupTime) {
      alertify.error('Please select followup date and time');
      return;
    }

    const data = {
      id: this.selectedEmail.id,
      followup_date: this.followupDate,
      followup_time: this.followupTime,
      followup_notes: this.followupNotes
    };

    this.service.post('marketing/crm-tracking/crm-tracking.php?type=updateEmailFollowup', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Followup updated successfully');
        this.closeView();
        this.getFollowupEmails();
      } else {
        alertify.error(response.message || 'Error updating followup');
      }
    }, error => {
      console.error('Error updating followup:', error);
      alertify.error('Error updating followup');
    });
  }

  refresh() {
    this.getFollowupEmails();
  }
}


