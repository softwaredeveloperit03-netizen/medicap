import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-working',
  templateUrl: './working.component.html',
  styleUrls: ['./working.component.css']
})
export class WorkingComponent implements OnInit {

  
  selectedFile: any;
  
  constructor(private service: DataAccessService,private router: Router ) {}

  ngOnInit(): void {
  }
  onFileChanged6(event) {
    this.selectedFile = event.target.files[0];
  }
  

  save(data) {
    // if(!data.valid){
      //   alertify.error('Please Select Attachment')
      //   return;
      // }
      const temp = data.value;
      const uploadData = new FormData();
      for (let key in temp) {
        let value = temp[key];
        uploadData.append(key, value);
      } 
      if (this.selectedFile !== undefined) {
        uploadData.append('strategies', this.selectedFile, this.selectedFile.name);
      }
      
      
      this.service.post('qa/all.php?type=saveworking_standard_bookform',uploadData).subscribe(response => {
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
