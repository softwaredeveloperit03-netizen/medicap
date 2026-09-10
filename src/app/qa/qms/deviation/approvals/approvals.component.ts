import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-approvals',
  templateUrl: './approvals.component.html',
  styleUrls: ['./approvals.component.css']
})
export class ApprovalsComponent implements OnInit {

  results;
  attchments
  observed;
  isView=false;
    emp_id: string;
    isDIGI: boolean;
    isButton: boolean=true;
    Deviation: any;
  constructor(private service: DataAccessService) {
    this.dept = localStorage.getItem('department');

   }
   dept;

  ngOnInit() {
    this.getPendingReview();
    this.getObserved();
    this.dept = localStorage.getItem('department');

  }
  getObserved(){
    this.observed = [
      { observed_in: 'Negligible Risk', value: false},
      { observed_in: 'Minor Risk', value: false},
      { observed_in: 'Moderate Risk', value: false},
      { observed_in: 'Major Risk', value: false},
      { observed_in: 'Severe Risk', value: false},
   

    ]
  }
  final_value;
  updateObserve(checked: boolean, index: number) {
    this.observed[index].value = checked;

    // Uncheck all other checkboxes
    for (let i = 0; i < this.observed.length; i++) {
        if (i !== index) {
            this.observed[i].value = false;
        }
       
      }
    
}
  getPendingReview() {
    this.service.get('purchase/deviation.php?type=get_deviations&status1=risk_assessment').subscribe(response => {
      this.results = response;
    });
  }
  
  selectedResult=[];
  initial_risk_quality_assessment;
  proceed(index){
    this.isView=true;
    this.selectedResult=this.results[index];
    this.get_deviation_Attachment(this.selectedResult['deviation_no'])
    this.initial_risk_quality_assessment=JSON.parse(this.selectedResult['initial_risk_quality_assessment']);
    console.log(this.initial_risk_quality_assessment);
  }
  get_deviation_Attachment(dev_id){
    this.service.get('deviation.php?type=get_deviation_Attachment&deviation_no='+dev_id+'&department1=Purchase').subscribe(response => {
      this.attchments = response;
    })
  }
  selectedFile: File;
  onFileChanged(event) {
    if(event.target.files.length === 1) {
      this.selectedFile = event.target.files[0];
    }
  }
  document_title;
  document_title1;
  save(type,forr) {
 
    const uploadData = new FormData();

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }
    if(forr=='document_title1'){
      uploadData.append("document_title",this.document_title1);
    }else if(forr=='document_title'){
      uploadData.append("document_title",this.document_title);
    } 
    uploadData.append("dev_id",this.selectedResult['deviation_no']);
    uploadData.append("department",'Purchase');
    uploadData.append("type",type);

   
 
    
    this.service.post('deviation.php?type=save_deviation_Attachment', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(' saved successfully');
       this.get_deviation_Attachment(this.selectedResult['deviation_no']);
       if(forr=='document_title1'){
        this.document_title1='';
         }else if(forr=='document_title'){
          this.document_title='';
         }
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
  View(url){
    url = this.service.url + '../../upload/deviation/' + url;
    window.open(url, '_blank');
     // window.open(this.selectedResult['documents']);
  }
  risk_list=[];
  savetemp(data){

    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['risk']=this.observed;
    this.risk_list[this.risk_list.length]=temp;
    console.log(this.risk_list)
    // data.resetForm();
  }

  openDigiSign(value){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.Deviation=value
  }

  loginPassward ='';
  digiSign(data){

    if (!data.valid) {
      alert('Passward OR Login PIN Required!!!!');
      return;
    }
 
    this.service.get('login.php?type=checkDigiSIgn&mpin=' + this.loginPassward +'&emp_id=' + this.emp_id).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Digi-Sign Verified successfully');
        this.isDIGI = false;
        this.isButton = false;
        this.loginPassward ='';
        this.saveDeviation(this.Deviation)
      } else {
        alertify.error('Digi-Sign Not Verified');
      }
    });
  }
    approveIndend(status: any) {
        throw new Error('Method not implemented.');
    }
    status(status: any) {
        throw new Error('Method not implemented.');
    }
  saveDeviation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['department']= this.dept;
    temp['deviation_no']=this.selectedResult['deviation_no'];
    temp['risk']=this.observed;
    this.service.post('purchase/deviation.php?type=conditional_final_approval', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView=false;
        this.getPendingReview();
        // this.router.navigate(['/qms/deviation']);
        alert(' Successfully Proceed  ');
        this.isButton=true;
      } else {
        alert('Failed: An error occured, please try again!');
      }
    })
  }

}
