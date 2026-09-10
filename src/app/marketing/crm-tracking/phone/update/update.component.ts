import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router, ActivatedRoute } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-phone-update',
  templateUrl: './update.component.html',
  styleUrls: ['./update.component.css']
})
export class PhoneUpdateComponent implements OnInit {

  callId: string = '';
  loading = false;

  // Call Details (Stage 2)
  callType: string = 'Outgoing';
  callDate: string = '';
  callTime: string = '';
  callDuration: string = '';
  calledBy: string = '';
  status: string = 'Completed';
  responseReceived: string = 'No';
  responseDetails: string = '';
  followupRequired: string = 'No';
  followupDate: string = '';
  followupTime: string = '';
  remarks: string = '';

  // Dropdown data
  users: any[] = [];

  constructor(
    private service: DataAccessService, 
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.callId = this.route.snapshot.paramMap.get('id') || '';
    this.getUsers();
    this.calledBy = localStorage.getItem('emp_id') || '';
    const now = new Date();
    this.callDate = now.toISOString().split('T')[0];
    this.callTime = now.toTimeString().split(' ')[0].substring(0, 5);
    
    if (this.callId) {
      this.loadCallData();
    }
  }

  getUsers() {
    this.service.get('common.php?type=getUsers').subscribe((response: any) => {
      this.users = response || [];
    }, error => {
      console.error('Error fetching users:', error);
      this.users = [];
    });
  }

  loadCallData() {
    this.loading = true;
    this.service.get(`marketing/crm-tracking.php?type=getPhoneCalls&id=${this.callId}`).subscribe((response: any) => {
      if (response && response.length > 0) {
        const call = response[0];
        this.callType = call.call_type || 'Outgoing';
        this.callDate = call.call_date || this.callDate;
        this.callTime = call.call_time || this.callTime;
        this.callDuration = call.call_duration || '';
        this.calledBy = call.called_by || this.calledBy;
        this.status = call.status || 'Completed';
        this.responseReceived = call.response_received || 'No';
        this.responseDetails = call.response_details || '';
        this.followupRequired = call.followup_required || 'No';
        this.followupDate = call.followup_date || '';
        this.followupTime = call.followup_time || '';
        this.remarks = call.remarks || '';
      }
      this.loading = false;
    }, error => {
      console.error('Error loading call data:', error);
      this.loading = false;
    });
  }

  updateCallDetails(form: any) {
    if (!form.valid) {
      alertify.error('Please fill all required fields');
      return;
    }

    const data = {
      id: this.callId,
      call_type: this.callType,
      call_date: this.callDate,
      call_time: this.callTime,
      call_duration: this.callDuration,
      called_by: this.calledBy,
      status: this.status,
      response_received: this.responseReceived,
      response_details: this.responseDetails || '',
      followup_required: this.followupRequired,
      followup_date: this.followupDate || '',
      followup_time: this.followupTime || '',
      remarks: this.remarks || ''
    };

    this.service.post('marketing/crm-tracking.php?type=updatePhoneCall', data).subscribe((response: any) => {
      if (response.status === 'success') {
        alertify.success('Call details updated successfully');
        this.router.navigate(['/marketing/crmTracking/phone/log']);
      } else {
        alertify.error(response.message || 'Error updating call details');
      }
    }, error => {
      console.error('Error updating call:', error);
      alertify.error('Error updating call details');
    });
  }

  cancel() {
    this.router.navigate(['/marketing/crmTracking/phone/new2']);
  }
}
