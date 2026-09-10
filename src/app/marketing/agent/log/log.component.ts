import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-log',
  templateUrl: './log.component.html',
  styleUrls: ['./log.component.css']
})
export class LogComponent implements OnInit {
  results: any[] = [];
  selectresult: any = {};
  clients: any[] = [];
  isView = false;

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  rights: any;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingAgents();
    this.get_rights();
  }

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          localStorage.getItem('department')
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
      });
  }

  getPendingAgents() {
    this.service.get('marketing/agent.php?type=getAgentsLog').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    });
  }

  view(index: number) {
    this.selectresult = this.results[index];
    this.clients = this.selectresult['clients'] || [];
    this.isView = true;
  }

  downloadPdf() {
    if (!this.results?.length) {
      alertify.error('No records to download');
      return;
    }
    this.service.open('pdf1/marketing.php?type=agentLog');
  }

  downloadDetailPdf() {
    const id = this.selectresult?.id;
    if (!id) {
      alertify.error('No agent selected');
      return;
    }
    this.service.open('pdf1/marketing.php?type=agentLogDetail&id=' + encodeURIComponent(id));
  }
}
