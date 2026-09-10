import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-quarantine',
  templateUrl: './quarantine.component.html',
  styleUrls: ['./quarantine.component.css']
})
export class QuarantineComponent implements OnInit {

  selectedFile: any;
  selectedFile2: any;
  selectedFile3: any;
  fromlevel1 = [];
  fromlevel1Data: any;
   

  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {
  }
  onFileChanged1(event) {
    this.selectedFile = event.target.files[0];
  } 
  addlabel1(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    let tempData = [];
    this.fromlevel1[this.fromlevel1.length] = temp;
    console.log(this.fromlevel1);
    data.resetForm();
  }

  
  save(data) {
    
      let temp = data.value;
      const uploadData = new FormData();
      for (let key in temp) {
        let value = temp[key];
        uploadData.append(key, value);
      } 
      if (this.selectedFile !== undefined) {
        uploadData.append('doc', this.selectedFile, this.selectedFile.name);
      }
     temp['fromlevel1']=this.fromlevel1;
     this.service.post('qa/all.php?type=saverefrence_standard_bookform',uploadData).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  delData(index) {
    this.fromlevel1.splice(index, 1);
  }
  del(index) {
    this.fromlevel1.splice(index, 1);
  }
}
