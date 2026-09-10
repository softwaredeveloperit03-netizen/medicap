import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-condition',
  templateUrl: './condition.component.html',
  styleUrls: ['./condition.component.css']
})
export class ConditionComponent implements OnInit {

  results;
  attchments
  observed;
  isView=false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingReview();
    this.getObserved();
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
    this.service.get('purchase/deviation.php?type=get_deviations&status1=conditional_final').subscribe(response => {
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
  save(type) {
 
    const uploadData = new FormData();

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }
    uploadData.append("document_title",this.document_title);
    uploadData.append("dev_id",this.selectedResult['deviation_no']);
    uploadData.append("department",'Purchase');
    uploadData.append("type",type);

   
 
    
    this.service.post('deviation.php?type=save_deviation_Attachment', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success(' saved successfully');
       this.get_deviation_Attachment(this.selectedResult['deviation_no']);
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
  saveDeviation(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['department']='Purchase';
    temp['deviation_no']=this.selectedResult['deviation_no'];
    temp['risk']=this.observed;
    this.service.post('purchase/deviation.php?type=qaconditional_final_approval', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        // this.router.navigate(['/qms/deviation']);
        this.isView=false;
        this.getPendingReview();
        alert('Deviation Investigated And Action Taken Successfully Proceed For Risk Assessment');
      } else {
        alert('Failed: An error occured, please try again!');
      }
    })
  }


}
