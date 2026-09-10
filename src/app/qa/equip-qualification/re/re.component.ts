import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-re',
  templateUrl: './re.component.html',
  styleUrls: ['./re.component.css']
})
export class ReComponent implements OnInit {

  constructor(private service: DataAccessService) { }
  selectedFile: File;
  selectedFile2: File;
  ngOnInit(): void {
  }

  save(data) {
    if(!data.valid){
      alertify.error('Please Select Challan');
      return;
    }
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
    if (this.selectedFile !== undefined) {
      uploadData.append('refrences_doc', this.selectedFile, this.selectedFile.name);
    }
    if (this.selectedFile2 !== undefined) {
      uploadData.append('support_doc', this.selectedFile2, this.selectedFile2.name);
    }
    
    this.service.post('qa/all.php?type=saveRequalificationform',uploadData).subscribe(response => {
     
        alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
        data.resetForm();
    });
  }
  onFileChanged2(event) {
    this.selectedFile = event.target.files[0];
  }
  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }
  }
  



