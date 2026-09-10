import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {

  authorizations: any[] = [];
  loading = false;
  isView = false;
  selectedAuthorization: any = {};
  actionLogs: any[] = [];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getAuthorizations();
  }

  getAuthorizations() {
    this.loading = true;
    this.service.get('exports/exports.php?type=getAuthorizationLogs').subscribe((response: any) => {
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
      console.error('Error fetching authorization logs:', error);
      alertify.error('Error fetching authorization logs');
      this.loading = false;
    });
  }

  view(index: number) {
    this.selectedAuthorization = this.authorizations[index];
    // Parse action_log JSON
    if (this.selectedAuthorization.action_log) {
      try {
        this.actionLogs = JSON.parse(this.selectedAuthorization.action_log);
      } catch (e) {
        this.actionLogs = [];
        console.error('Error parsing action log:', e);
      }
    } else {
      this.actionLogs = [];
    }
    this.isView = true;
  }

  closeView() {
    this.isView = false;
    this.selectedAuthorization = {};
    this.actionLogs = [];
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

  getActionClass(action: string): string {
    if (action.toLowerCase().includes('approved')) {
      return 'label label-success';
    } else if (action.toLowerCase().includes('rejected')) {
      return 'label label-danger';
    } else if (action.toLowerCase().includes('created')) {
      return 'label label-info';
    } else if (action.toLowerCase().includes('updated')) {
      return 'label label-warning';
    }
    return 'label label-default';
  }
}
