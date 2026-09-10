import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-annoucement',
  templateUrl: './annoucement.component.html',
  styleUrls: ['./annoucement.component.css']
})
export class AnnoucementComponent implements OnInit {

  isNew = false;
  isNew1 = false;
  departments;
  isNewForm = false;
  entries;
  entries1;
  entries2;
  auditor;
  p_id = '';
  isChecker;
  isApprover;

  selectedStability = [];
  // tslint:disable-next-line: variable-name
  auditors_name = '';
  selectedPid;
  constructor(private service: DataAccessService, private router: Router) {
   }

  ngOnInit() {
     this.getInspectionPlan();
     this.getInspectionTeam();


     if (localStorage.getItem('approver') === 'true') {
      this.isApprover = true;
     } else {
      this.isApprover = false;
     }

     if (localStorage.getItem('checker') === 'true') {
       this.isChecker = true;
      } else {
       this.isChecker = false;
      }

  }

  getAuditors(p_id) {
    this.auditor = undefined;
    this.service.get('audit-trails.php?type=getAuditors&p_id=' + p_id).subscribe(response => {
      this.auditor = response;
    });
  }

  check(value) {
    this.service.get('audit-trails.php?type=updateStatusCheck&id=' + value).subscribe(response => {
      if (response['status'] === 'success') {
        this.getInspectionAnnouncement(this.p_id);
      }
    });
  }

  approve(value) {
    this.service.get('audit-trails.php?type=updateStatus&id=' + value).subscribe(response => {
      if (response['status'] === 'success') {
        this.getInspectionAnnouncement(this.p_id);
      }
    });
  }


  saveForm(inspectionannouncement) {

    const data = inspectionannouncement.value;
    data['p_id'] = this.selectedPid;
    this.service.post('audit-trails.php?type=saveInspectionAnnouncement', JSON.stringify(data))
    .subscribe(response => {
      if (response['status'] === 'success') {
        inspectionannouncement.resetForm();
        this.getInspectionAnnouncement(this.p_id);
        this.isNew = false;
        alert('Successfully send for Approval');
      } else {
        alert('An error has occurred, please try again');
      }
      },
    (error: Response) => {
      if (error.status === 400) {
        alert('An error has occurred.');
      } else {
        alert('An error has occurred, http status:' + error.status);
      }
    });
  }

  getInspectionPlan() {
    this.service.get('audit-trails.php?type=getInspectionPlan').subscribe(response => {
      this.entries = response;
    });
  }

  getInspectionTeam() {
    this.service.get('audit-trails.php?type=getInspectionTeam').subscribe(response => {
      this.entries2 = response;
    });
  }

  getTeam(pid) {
    this.isNew1 = true;
    this.entries2 = undefined;
    this.service.get('audit-trails.php?type=getInspectionTeamByid&p_id=' + pid).subscribe(response => {
      this.entries2 = response;
    });
  }

  onAddTeam(selected) {
    this.isNew = true;
    this.selectedPid = selected.p_id;
    this.getAuditors(selected.p_id);
    this.getInspectionAnnouncement(selected.p_id);
  }

  getInspectionAnnouncement(p_id) {
    this.entries1 = [];
    this.service.get('audit-trails.php?type=getInspectionAnnouncement&p_id=' + p_id).subscribe(response => {
      this.entries1 = response;
    });
  }

  close() {
   this.router.navigate(['/qa/audit']);
  }

}
