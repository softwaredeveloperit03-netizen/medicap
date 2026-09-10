import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  saveBtn=true;

  constructor(private service: DataAccessService,private router:Router) { }
  Name_of_Department;
  ngOnInit(): void {
    this.Name_of_Department=localStorage.getItem('department');
  }

  incident_relateds=[
      {origin:'Procedure',value:false},
      {origin:'Process',value:false},
      {origin:'Equipment',value:false},
      {origin:'Standard',value:false},
      {origin:'Batch Size',value:false},
      {origin:'Others',value:false}  
    ];
    
  classification_inr=[
      {origin:'Critical',value:false},
      {origin:'Major',value:false},
      {origin:'Minor',value:false}  
    ];

  potential_impact=[
      {origin:'Quality',value:false},
      {origin:'Yield',value:false},
      {origin:'GMPs',value:false} , 
      {origin:'Manufacturing Process',value:false},  
      {origin:'Other',value:false}  
    ]
  selectedIncidentRelated = '';
  selectedClassificationInr = '';
  selectedPotentialImpact = '';
  



  selectedFile:File;
  onFileChanged(event) {   
    this.selectedFile = event.target.files[0];     
}
  selectedFile1:File;
  onFileChanged1(event) {   
    this.selectedFile1 = event.target.files[0];     
}
  selectedFile2:File;
  onFileChanged2(event) {   
    this.selectedFile2 = event.target.files[0];     
}



save(data) {

  if(!data.valid){
    alertify.error('All fields are required!');
    this.saveBtn=true;
    return;
  }

  let temp = data.value;


  this.service.post('qms/newIncident.php?type=saveIncident',JSON.stringify(temp)).subscribe(response=>{
    if(response['status']==='success'){
       alertify.success('data save Successfuly');
      data.resetForm();
    }else{
      alertify.error('Error Occured');
    }
  });
 
 
  
  
}

}

 