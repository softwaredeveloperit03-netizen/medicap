import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare var alertify: any;
@Component({
  selector: 'app-content-master-log',
  templateUrl: './content-master-log.component.html',
  styleUrls: ['./content-master-log.component.css']
})
export class ContentMasterLogComponent implements OnInit {
  isView = false;
   
  departments;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getDepartments();
    this.getContentMaster();
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
  
      this.service.post('qa/document.php?type=SaveDocIndexForTrainingMaster',uploadData).subscribe(response=>{
        if(response['status']==='success'){
           alertify.success('data save Successfuly');
           this.getContentMaster();
           this.isView = false
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

  getContentMaster() {
    this.service.get('qa/document.php?type=getContentMaster').subscribe(response => {
      this.documents = response;
    });
  }
  documents;
 

  
  searchQuery;


  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.documents; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.documents.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

 
     
  viewDoc(url){
    url = this.service.url + '../../upload/documentIndex/' + url;
    window.open(url, '_blank');
   }
 

  
 

 
}
