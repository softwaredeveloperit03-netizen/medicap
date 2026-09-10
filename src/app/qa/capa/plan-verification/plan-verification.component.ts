import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-plan-verification',
  templateUrl: './plan-verification.component.html',
  styleUrls: ['./plan-verification.component.css']
})
export class PlanVerificationComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  capa;data;

  ngOnInit(): void {
    this.getCapa();
    this.getPlan();
  }

  getCapa(){
    this.service.get('qa/all2.php?type=getCapaNo').subscribe((response:any) => {
      this.capa = response;
     
    });
  }

  getPlan(){
    this.service.get('qa/all2.php?type=getApprovalPlan').subscribe((response:any) => {
      this.data = response;
     
    });
  }

  capa_no;title;dept;date1;date2;key;success;action;resource;head;review;approval;comments;comments1
  isView=false;
  selectedResult=[];
  view(index){
    this.selectedResult=this.data[index]
    this.isView=true;
    this.capa_no=this.selectedResult['effectiveness_id'];
    this.title=this.selectedResult['plan_title'];
    this.dept=this.selectedResult['responsible_department'];
    this.date1=this.selectedResult['initiation_date'];
    this.date2=this.selectedResult['proposed_completion_date'];
    this.key=this.selectedResult['key_Performance'];
    this.success=this.selectedResult['Criteria_success'];
    this.action=this.selectedResult['strategies_action'];
    this.resource=this.selectedResult['resource_req'];
    this.head=this.selectedResult['department_head'];
    this.review=this.selectedResult['review_date'];
    this.approval=this.selectedResult['approvel_status'];
    this.comments=this.selectedResult['department_head_contains'];
    this.comments1=this.selectedResult['comments'];
    console.log(this.selectedResult);
  }
  verifyComments;
  save(data,status) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    this.service.post('qa/all2.php?type=save_verify&status=' + status + '&capa_no=' + this.capa_no +'&cmt='+ this.verifyComments  ,JSON.stringify(data.value)).subscribe( response => {    
        if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/qa/capa'])
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
