import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-history',
  templateUrl: './history.component.html',
  styleUrls: ['./history.component.css']
})
export class HistoryComponent implements OnInit {

  constructor(private service: DataAccessService) { }
  
  ngOnInit() {
    this.getDetails()
  }

  isNew=false
new()
{
 this.isNew=true
}

data;
getDetails()
{
  this.service.get('qa/all2.php?type=get_revision').subscribe((response:any) => {
    this.data = response;
   
  });
}

  selectedFile2: File;

  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
  }


  save(data) {
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
    if (this.selectedFile2 !== undefined) {
      uploadData.append('sopDoc', this.selectedFile2, this.selectedFile2.name);
    }

    this.service.post('qa/all2.php?type=sopRevHistory', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Save Successfully');
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

  

}
