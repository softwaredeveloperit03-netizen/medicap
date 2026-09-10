import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;


@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

 
  constructor(private service: DataAccessService,private router : Router) {  }

  ngOnInit(): void {
    this.getPendingRevisionRequest();
    this.department = localStorage.getItem('department');
    this.getDepartments();
  }

  department = localStorage.getItem('department');

  result;
  logResults: any[] = [];
  logSearch = '';
  logStatus = 'All';
  logReqFor = 'All';
  isView = false;
  isLog = false;
  isRevision = false;
  selectedResult =[];

  getPendingRevisionRequest() {
    this.service.get('revision.php?type=getPendingRevisionRequest').subscribe((response: any) => {
      this.result = response;
     });
  }

  openLog(): void {
    this.isLog = true;
    this.isView = false;
    this.getRevisionRequestLog();
  }

  closeLog(): void {
    this.isLog = false;
  }

  getRevisionRequestLog() {
    this.service
      .get(
        'revision.php?type=getRevisionRequestLog&status=' +
          encodeURIComponent(this.logStatus) +
          '&req_for=' +
          encodeURIComponent(this.logReqFor)
      )
      .subscribe((response: any) => {
        this.logResults = Array.isArray(response) ? response : [];
      });
  }

  get filteredLogResults(): any[] {
    const q = (this.logSearch || '').trim().toLowerCase();
    if (!q) {
      return this.logResults;
    }
    return this.logResults.filter((row: any) =>
      Object.keys(row || {}).some((k) => row[k] != null && String(row[k]).toLowerCase().includes(q))
    );
  }

  view(i){
    const source = this.isLog ? this.filteredLogResults : this.result;
    this.selectedResult = source[i];
    this.isView = true;
  }
  revisionComment = '';

  revision(i){
    this.selectedResult = this.result[i];
    this.isRevision = true;
    this.revisionComment = '';
  }

  departments;

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }


  viewFile(url) {
    url = this.service.url + '../../upload/Sops/' + url+'?v=1';
   window.open(url, '_blank');
 }



 approveReq(){

    let temp = this.selectedResult;

      this.service.post('revision.php?type=approveRevisionRequest&id='+this.selectedResult['id'], JSON.stringify(temp)).subscribe(
        (response) => {
          if (response['status'] === 'success') {
            alert('Reviewed Successfully !!!!!!');
            this.getPendingRevisionRequest();
            this.getRevisionRequestLog();
            this.isView = false;
            
          } else {
            alert('Failed: An error occurred, please try again!');
          }
        }
      );

 }
 
}
