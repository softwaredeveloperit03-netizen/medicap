import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { FormBuilder } from '@angular/forms';
import { Router } from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
   


   departments;
  constructor(private service: DataAccessService,private router: Router,private formBuilder: FormBuilder, public fb: FormBuilder) { }

  ngOnInit(): void {
    this.getDepartments();
  }

  selectedFile:File;

  onFileChanged(event) {
    if(event.target.files.length === 1) {
      this.selectedFile = event.target.files[0];
    }
  }
 
  saveForm(Form){
      if(!Form.valid){
        alertify.error('All fields are required');
        return;
      }
 
      let temp = Form.value;


      const uploadData = new FormData();

      for(let key in temp){
        let value=temp[key];
        uploadData.append(key, value);
      }


    if (this.selectedFile !== undefined) {
      uploadData.append('document', this.selectedFile, this.selectedFile.name);
    }
  
      this.service.post('qa/document.php?type=SaveDocIndex',uploadData).subscribe(response=>{
        if(response['status']==='success'){
           alertify.success('data save Successfuly');
          Form.resetForm();
        }else{
          alertify.error('Error Occured');
        }
      });
    }



  getDepartments() {
    this.service.get('common.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }
  

}