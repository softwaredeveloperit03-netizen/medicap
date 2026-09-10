import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import{Router} from '@angular/router';

@Component({
  selector: 'app-head-approval',
  templateUrl: './head-approval.component.html',
  styleUrls: ['./head-approval.component.css']
})
export class HeadApprovalComponent implements OnInit {

  constructor(private service:DataAccessService,private router : Router) { }
  data;
  capa;
  ngOnInit(): void {
    this.getPlan();
    this.getCapa();
  }

  getCapa(){
    this.service.get('qa/all2.php?type=getCapaNo').subscribe((response:any) => {
      this.capa = response;
     
    });
  }

  getPlan(){
    this.service.get('qa/all2.php?type=getPlan').subscribe((response:any) => {
      this.data = response;
     
    });
  }

  capa_no;title;dept;date1;date2;key;success;action;resource;head;review;approval;comments
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
    console.log(this.selectedResult);
  }

  qa_head_comm;
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
      uploadData.append('supporing_doc', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('qms/capa.php?type=save_approvel&status=' + status + '&capa_no=' + this.capa_no +'&comments='+ this.qa_head_comm ,uploadData).subscribe( response => {    
        if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/qa/capa'])
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
