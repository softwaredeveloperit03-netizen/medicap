import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-change-request',
  templateUrl: './change-request.component.html',
  styleUrls: ['./change-request.component.css']
})
export class ChangeRequestComponent implements OnInit {
  selectedFile;
  approvel_requird = 'No';
  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {

  }

onFileChanged3(event) {
    this.selectedFile = event.target.files[0];
  }

  save(data) {
    console.log(data.value);
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
  
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
    if (this.selectedFile !== undefined) {
      uploadData.append('attachment_doc1', this.selectedFile, this.selectedFile.name);
    }

    this.service.post('it/itall.php?type=savechangerequestform',uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        // this.router.navigate(['/checklist']);
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }
}
