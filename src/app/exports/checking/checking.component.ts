import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-checking',
  templateUrl: './checking.component.html',
  styleUrls: ['./checking.component.css']
})
export class CheckingComponent implements OnInit {

  authorizations: any[] = [];
  loading = false;
  isView = false;
  selectedAuthorization: any = {};

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getAuthorizations();
  }

  getAuthorizations() {
    this.loading = true;
    this.service.get('exports/exports.php?type=getAuthorizations').subscribe((response: any) => {
      this.authorizations = (response || []).map((auth: any) => {
        // Normalize file URLs to full paths
        if (auth.authorization_copy_file_url && !auth.authorization_copy_file_url.startsWith('http')) {
          auth.authorization_copy_file_url = this.service.domain.replace('/php/', '/') + auth.authorization_copy_file_url.replace('../../', '');
        }
        if (auth.annexures_file_url && !auth.annexures_file_url.startsWith('http')) {
          auth.annexures_file_url = this.service.domain.replace('/php/', '/') + auth.annexures_file_url.replace('../../', '');
        }
        if (auth.digital_signature_file_url && !auth.digital_signature_file_url.startsWith('http')) {
          auth.digital_signature_file_url = this.service.domain.replace('/php/', '/') + auth.digital_signature_file_url.replace('../../', '');
        }
        return auth;
      });
      this.loading = false;
    }, error => {
      console.error('Error fetching authorizations:', error);
      alertify.error('Error fetching authorizations');
      this.loading = false;
    });
  }

  view(index: number) {
    this.selectedAuthorization = this.authorizations[index];
    this.isView = true;
  }

  closeChecking() {
    this.isView = false;
    this.selectedAuthorization = {};
  }

  refresh() {
    this.getAuthorizations();
  }

  getStatusClass(status: string): string {
    switch(status) {
      case 'Active': return 'label label-success';
      case 'Pending': return 'label label-warning';
      case 'Expired': return 'label label-danger';
      case 'Suspended': return 'label label-danger';
      case 'Revoked': return 'label label-danger';
      default: return 'label label-info';
    }
  }

  getApprovalStatusClass(status: string): string {
    switch(status) {
      case 'Approved': return 'label label-success';
      case 'Rejected': return 'label label-danger';
      case 'Pending': return 'label label-warning';
      default: return 'label label-warning';
    }
  }


  Approve() {
    console.log(this.selectedAuthorization);
    this.service.post('exports/exports.php?type=approveChecking', this.selectedAuthorization).subscribe((response: any) => {
      console.log(response);
      alertify.success('Checking approved successfully');
      this.getAuthorizations();
      this.closeChecking();
    }, error => {
      console.error('Error approving checking:', error);
      alertify.error('Error approving checking');
    });
  }
}

