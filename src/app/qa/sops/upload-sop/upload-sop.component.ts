import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-upload-sop',
  templateUrl: './upload-sop.component.html',
  styleUrls: ['./upload-sop.component.css']
})
export class UploadSOPComponent implements OnInit {
  
  departments;
  forms;
  isUploadSOP = 0;
  selectedFile1: File;

  sops = [];
  
  constructor(private service: DataAccessService, private router:Router) { }

  ngOnInit() {
    this.getUploadedSops();
   // this.getDepartments();
  }

  getUploadedSops() {
    const url = this.service.url + 'upload/';
    this.service.get('sops.php?type=getUploadedSops').subscribe(response => {
      this.sops = JSON.parse(JSON.stringify(response));
      this.sops.forEach(element => {
        if (element.flowchart !== '' && element.flowchart !== undefined) {
          element.flowchart = url + element.flowchart;
        } else {
          element.flowchart = 'unavailable';
        }

        if (element.sop_file !== '' && element.sop_file !== undefined) {
          element.sop_file = url + element.sop_file;
        } else {
          element.sop_file = 'unavailable';
        }
      });
    });
  }

  /*getDepartments() {
    this.service.get('hrDepartment.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getForms(department_name) {
    this.service.get('gmp.php?type=getForms&department_name=' + department_name).subscribe(response => {
      this.forms = response;
    });
  }*/

  submitSOP(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    const uploadData = new FormData();
    if (this.isUploadSOP === 1) {
      uploadData.append('sop', this.selectedFile1, this.selectedFile1.name);
    }
    uploadData.append('title', data.value.sop_title);
    uploadData.append('sop_for', data.value.sop_for);
    uploadData.append('version_no', data.value.version_no);

    this.service.post('sops.php?type=uploadSop', uploadData).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));

      if (result.status === 'success') {
        alert('Data saved successfully');
        data.resetForm();
        this.router.navigate(['/sops']);
      } else {
        alert('An error occured');
      }
    });
  }


  onFileChanged1(event): void {
    if (event.target.files.length > 0) {
      this.selectedFile1 = event.target.files[0];
      this.isUploadSOP = 1;
    } else {
      this.isUploadSOP = 0;
    }
  }


  viewLink(link) {
    if (link !== 'unavailable') {
      window.open(link, '_blank');
    } else {
      alert('Not Available');
    }
  }

}
