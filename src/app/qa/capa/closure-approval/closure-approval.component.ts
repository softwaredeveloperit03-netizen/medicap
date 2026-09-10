import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-closure-approval',
  templateUrl: './closure-approval.component.html',
  styleUrls: ['./closure-approval.component.css']
})
export class ClosureApprovalComponent implements OnInit {
  
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPlan();
  }

  data;

  getPlan(){
    this.service.get('qa/all2.php?type=getClosure').subscribe((response:any) => {
      this.data = response;
     
    });
  }
  capa_no;title;dept;date1;date2;key;success;action;resource;head;review;approval;comments;comments1
  verifyComments;closureComments;

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
    this.verifyComments=this.selectedResult['verify_closure_comments'];
    this.closureComments=this.selectedResult['closureComments'];
    console.log(this.selectedResult);
  }
  approval_comments
  selectedFile2: File;

  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }


  save(data,status) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
    if (this.selectedFile2 !== undefined) {
      uploadData.append('support_document', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('qa/all2.php?type=save_closure_approval&status=' + status + '&capa_no=' + this.capa_no +'&verify_closure_comments='+ this.verifyComments +'&closure_comments='+this.closureComments +'&approval_comments='+this.approval_comments,uploadData).subscribe( response => {    
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
