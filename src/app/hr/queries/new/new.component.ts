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

  screenshot: File;
  formData = new FormData();

  constructor(private service:DataAccessService,private router:Router) { }

  ngOnInit() {
  }


  onFileChanged(event) {
    if (event.target.files.length !== 0) {
      let total_files = event.target.files.length; 
      for(let i=0; i<total_files; i++){
        this.formData.append('screenshot' + i, event.target.files[i]);
      }
      this.formData.append('total', total_files);
      
    }
  }

  submit(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;

    

    for (let key in temp) {
      let value = temp[key];
      this.formData.append(key, value);
    }
    this.service.post('queries.php?type=saveQuery', this.formData).subscribe(response => {
      if(response['status'] === 'success') {
        data.resetForm();
        this.router.navigate(['/queries']);
        alertify.success('Data Submitted Successfully!');
      } else {
        alertify.error(response['status']);
      }
      });
  }

}
