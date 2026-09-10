import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-awaiting-candidates',
  templateUrl: './awaiting-candidates.component.html',
  styleUrls: ['./awaiting-candidates.component.css']
})
export class AwaitingCandidatesComponent implements OnInit {
  candidates;
  isView=false;
  selectedReport=[];
  candidate_id;
  interviewers;
  designations;
  departments;
  constructor(private service: DataAccessService,private router:Router) {
   }

  ngOnInit() {
    this.getCandidates();
    this.getApprovedDepartments();
    this.getApprovedDesignations();
  }

  getCandidates() {
    this.service.get('hr/candidate.php?type=getAwaitingCandidates').subscribe(response => {
      this.candidates = response;
    });
  }

  getApprovedDesignations() {
    this.service.get('hrDepartment.php?type=getApprovedDesignations').subscribe(response => {
      this.designations = response;
    });
  }
  view(index){
    this.selectedReport=this.candidates[index];
    this.isView=true;
  }

  getApprovedDepartments() {
    this.service.get('hrDepartment.php?type=getApprovedDepartments')
    .subscribe(response => {
      this.departments = response;
    });
  }

  saveDecision(data) {
    this.service.post('hr/candidate.php?type=saveDecision&id='+this.selectedReport['id'],JSON.stringify(data.value)).subscribe(response=>{
      if(response['status']=='success'){
        data.resetForm();
        this.router.navigate(['/recruitment']);
        alert('data Saved Successfuly');
      }
    });
  }

  checkSelection(value) {}


}
