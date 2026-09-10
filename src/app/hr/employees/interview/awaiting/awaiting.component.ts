import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {

results;
designations;
qualifications;
isView=false;
selectedReport=[];
emp_id='';
questions = [
  {"question": "Educational Background: Does the candidate have the appropriate educational qualifications or training for this position?", "rating": 0},
  {"question": "Prior Work Experience: Has the candidate acquired similar skills or qualifications through past work experiences?", "rating": 0},
  {"question":"Technical Qualifications/Experience: Does the candidate have the technical skills necessary for this position?" ,"rating":0},
  { "question":"Verbal Communication: Did the candidate demonstrate effective communication skills during the interview?" ,"rating":0},
  { "question":"Candidate Enthusiasm: Did the candidate show enthusiasm for the position and the company?" ,"rating":0},
  { "question":"Knowledge of Company: Did the candidate show evidence of having researched the company prior to the interview?","rating":0},
  {"question":"Teambuilding/Interpersonal Skills: Did the candidate demonstrate, through his or her answers, good teambuilding/interpersonal skills?","rating" :0},
  { "question":"Initiative: Did the candidate demonstrate, through his or her answers, a high degree of initiative?","rating" :0},
  { "question" :"Time Management: Did the candidate demonstrate, through his or her answers, good time management skills?" ,"rating":0},
  {"question" :"Customer Service: Did the candidate demonstrate, through his or her answers, a high level of customer service skills/abilities?" ,"rating":0},
  {"question" :"Overall Impression and Recommendation: Final comments and recommendations for proceeding with the ","rating":0}
];
constructor(private service:DataAccessService,private router:Router) { }

ngOnInit() {
  this.getPendingInterviews();
  this.getDesignations();
  this.getApprovedQualifications() 
}

getPendingInterviews(){
  this.service.get('hr/interview.php?type=getPendingInterviews&employee=' + this.emp_id).subscribe(response=>{
    this.results=response;
  });

}
getApprovedQualifications() {
  this.service.get('common.php?type=getQualifications').subscribe(response => {
    this.qualifications = response;
  });
}
getDesignations() {
  this.service.get('hr/employee.php?type=getDesignations').subscribe(response => {
    this.designations = response;
  });
}
view(index){
  this.selectedReport=this.results[index];
  this.isView=true;
}
save(data) {
  if (!data.valid) {
    alertify.error('An error occured, please try again!');
    return;
  }
  this.service.post('hr/interview.php?type=saveInterview&id=' + this.selectedReport['id'],JSON.stringify(this.questions)).subscribe(response=>{
    if(response['status']=='success'){
      data.resetForm();
      this.router.navigate(['/recruitment']);
      alertify.success('candidate details updated Successsfuly');
      this.questions = [
        {"question": "Educational Background: Does the candidate have the appropriate educational qualifications or training for this position?", "rating": 0},
        {"question": "Prior Work Experience: Has the candidate acquired similar skills or qualifications through past work experiences?", "rating": 0},
        {"question":"Technical Qualifications/Experience: Does the candidate have the technical skills necessary for this position?" ,"rating":0},
        { "question":"Verbal Communication: Did the candidate demonstrate effective communication skills during the interview?" ,"rating":0},
        { "question" :"Candidate Enthusiasm: Did the candidate show enthusiasm for the position and the company?" ,"rating":0},
        { "question":"Knowledge of Company: Did the candidate show evidence of having researched the company prior to the interview?","rating":0},
        {"question":"Teambuilding/Interpersonal Skills: Did the candidate demonstrate, through his or her answers, good teambuilding/interpersonal skills?","rating" :0},
        { "question":"Initiative: Did the candidate demonstrate, through his or her answers, a high degree of initiative?","rating" :0},
        { "question" :"Time Management: Did the candidate demonstrate, through his or her answers, good time management skills?" ,"rating":0},
        {"question" :"Customer Service: Did the candidate demonstrate, through his or her answers, a high level of customer service skills/abilities?" ,"rating":0},
        {"question" :"Overall Impression and Recommendation: Final comments and recommendations for proceeding with the ","rating":0}
      ];
    }else{
      alertify.error('Errror Occured');
    }
  });
}
}