import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-finalreview',
  templateUrl: './finalreview.component.html',
  styleUrls: ['./finalreview.component.css']
})
export class FinalreviewComponent implements OnInit {

  isView = false;
  results;
  selectedRisk = [];
  assessment = [];
  analysis = [];
  evaluation = [];
  risks;
  risk = [];
  isRisk = false;
  justification = '';
  remark='';
  capa='Not Applicable'
  categories;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingEvaluation();
    this.getAssessment();
  }
  getAssessment(){
    this.categories=[
      {origin:'Deviation',value:false},
      {origin:'analytical Testing',value:false},
      {origin:'OOS',value:false},
      {origin:'OOT',value:false},
      {origin:'OOC',value:false},
      {origin:'PQR',value:false},
      {origin:'Product Complaint',value:false},
      {origin:'Recall / Mock Recall',value:false},
      {origin:'Self Inspection',value:false},
      {origin:'External Audit',value:false},
      {origin:'Internal Audit',value:false},
      {origin:'Invalid',value:false},
      {origin:'Risk Assessment',value:false},
      {origin:'Impact Assessment',value:false},
      {origin:'Change Control',value:false},
      {origin:'Non-conformance',value:false},
      {origin:'Incident',value:false},
      {origin:'Other',value:false}      
    ]
  }

  getPendingEvaluation() {
    this.service.get('qa/risk.php?type=getPendingQAHEAD').subscribe(response => {
      this.results = response;
    });
  }

  viewRisk(index) {
    this.selectedRisk = this.results[index];
    let assessment = this.selectedRisk["assessment_details"];
    this.assessment = assessment[0];
console.log("this.assessment =", this.assessment);

let analysis = this.selectedRisk["analysis_details"];
this.analysis = analysis[0];
console.log("this.analysis =", this.analysis);
let evaluation = this.selectedRisk["evaluation_details"];
    this.evaluation = evaluation[0];
console.log("this.evaluation =", this.evaluation);
      
  
     
        this.isView = true;
    } 
  

  selectRisk(index) {
    index = index - 1;
    this.risk = this.risks[index];
    this.isRisk = true;
  }

  saveControl(status) {
    let temp={}
   
    temp['status']=status;
    temp['risk_cmt_id']=this.selectedRisk["risk_cmt_id"];
    temp['capa']=this.capa;
    this.service.post('qa/risk.php?type=saveReviewFromQAhead&id=' + this.selectedRisk["id"],JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.isView = false;
        this.getPendingEvaluation();
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  selectedFile : File;
  selectedFile1 : File;
  selectedFile2 : File;
  onFileChanged(event) {   
    this.selectedFile = event.target.files[0];     
}
onFileChanged1(event) {     
    this.selectedFile1 = event.target.files[0];      
}
onFileChanged2(event) {    
    this.selectedFile2 = event.target.files[0];      
}
  save(data,status) {
    if(!data.valid){
      alertify.error('All fields are required!');
      return;
    }
    let temp = data.value;
    
    const uploadData = new FormData();

    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }

    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
      console.log(uploadData)
    }  
    if (this.selectedFile1 !== undefined) {
      uploadData.append('document1', this.selectedFile1, this.selectedFile1.name);
      console.log(uploadData)
    }  
    if (this.selectedFile2 !== undefined) {
      uploadData.append('document2', this.selectedFile2, this.selectedFile2.name);
      console.log(uploadData)
    }  
    
    let origin1 = [];
    for (let i = 0; i < this.categories.length; i++) {
      let origin = this.categories[i];
      if (origin['status']) {
        origin1[origin1.length] = origin['origin'];
      }
    }
    uploadData.append('origin',JSON.stringify(origin1));
    // uploadData.append('plan',JSON.stringify(this.capas));
    this.service.post('qms/capa.php?type=saveCAPA1&status='+status+'&capa='+this.capa+'&capaFrom=Risk',uploadData).subscribe(response=>{
      if (response['status'] === 'success') {
        alert('Saved Successfully');
        this.isView = false;
        this.getPendingEvaluation();
        this.saveControl(status);
         alertify.success('Successfully Saved');
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    })
  }

}
