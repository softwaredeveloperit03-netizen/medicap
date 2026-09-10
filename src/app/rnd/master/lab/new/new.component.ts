import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  selectedFile: File;
  selectedFile2:File;
  isUploadLic = 0;
  isuploadcertificate=0;
  fda_approved ="";
  branchList=[];
  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit(): void {
  }

  addBranch(data){
    if(!data.valid){
      alertify.error("All fiels are required");
      return;
    }
    this.branchList[this.branchList.length]=data.value;
    data.reset();
  }

  delData(index){
    this.branchList.splice(index,1);
  }

  onFileChanged(event) {
    this.selectedFile = event.target.files[0];
    this.isUploadLic = 1;
  }
  onFileChanged2(event){
    this.selectedFile2=event.target.files[0];
    this.isuploadcertificate=1;
  }
  saveform(formData){
    if(!formData.valid){
      alertify.error("All fiels are required");
      return;
    }
    const temp = formData.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    if (this.selectedFile !== undefined) {
      uploadData.append('lic', this.selectedFile, this.selectedFile.name);
    }
    if(this.selectedFile2 !== undefined){
      uploadData.append('certificate',this.selectedFile2,this.selectedFile2.name);
    }

    uploadData.append('branch', JSON.stringify(this.branchList));
    
    this.service.post('qc/lab.php?type=saveLab',uploadData).subscribe(response =>{
      if(response['status']=='success')
      {
        formData.resetForm();
        alertify.success("Succesfully Save Data");
        this.router.navigate(['/master/lab/log']);
      }else{
        alertify.error("Enable to save data!!!");
      }
    });
  }
}

