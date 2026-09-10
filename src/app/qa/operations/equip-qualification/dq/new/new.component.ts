import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  records;
  selectedSpec = [];
  isView = false;

  selectedFile: File;
  isUpload = false;
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingDQ();
  }

  getPendingDQ() {
    this.service.get('qa/qualification.php?type=getPendingDQ').subscribe(response => {
      this.records = response;
    });
  }

  viewSpecification(index) {
    this.selectedSpec = this.records[index];
    this.isView = true;
  }

  onFileChanged(event) {
    if (event.target.files.length > 0) {
      this.selectedFile = event.target.files[0];
      this.isUpload = true;
    } else {
      this.isUpload = false;
    }
  }

  save() {
    const uploadData = new FormData();
    if (this.isUpload) {
      uploadData.append('file', this.selectedFile, this.selectedFile.name);
      uploadData.append('id', this.selectedSpec['id']);

      this.service.post('qa/qualification.php?type=saveDQ&id=' + this.selectedSpec['id'], uploadData).subscribe(response => {
        if (response['status'] == 'success') {
          alert('Data Saved Successfully!');
          this.isView = false;
          this.isUpload = false;
          this.getPendingDQ();
        } else {
          alert('An error occured, please try again!');
        }
      });
    }
  }

}
