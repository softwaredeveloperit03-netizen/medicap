import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-document',
  templateUrl: './document.component.html',
  styleUrls: ['./document.component.css']
})
export class DocumentComponent implements OnInit {

  dev_id;
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
    this.dept = localStorage.getItem('department');
    console.log('department='+this.dept)
  }
  attchments
  results;
  isView=false;
  getPendingReview() {
    this.service.get('purchase/deviation.php?type=get_deviations&status1=pending&dept='+this.dept).subscribe(response => {
      this.results = response;
    });
  }
  selectedResult=[];
  proceed(index){
    this.isView=true;
    this.selectedResult=this.results[index];
    this.get_deviation_Attachment(this.selectedResult['deviation_no'])
  }
  // attchments;
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
  save(data) {
 
    const uploadData = new FormData();

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }
    uploadData.append("document_title",this.document_title);
    uploadData.append("dev_id",this.selectedResult['deviation_no']);
    uploadData.append("department",'Purchase');
    uploadData.append("type",'description');

   
 
    
    this.service.post('deviation.php?type=save_deviation_Attachment', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(' saved successfully');
       this.get_deviation_Attachment(this.dev_id);
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

  openDigiSign(formData){
    this.emp_id = localStorage.getItem('emp_id');
    this.isDIGI = true;
    this.Deviation=formData;
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
         this.approveIndend(this.status);
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
    temp['department']='Purchase';
    temp['deviation_no']=this.selectedResult['deviation_no'];
    this.service.post('deviation.php?type=saveInvestigation', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        // this.router.navigate(['/qms/deviation']);
        this.getPendingReview();
        this.isView=false;
        alert('Deviation Investigated And Action Taken Successfully Proceed For Risk Assessment');
        this.isButton=true;
      } else {
        alert('Failed: An error occured, please try again!');
      }
    })
  }

 
}
