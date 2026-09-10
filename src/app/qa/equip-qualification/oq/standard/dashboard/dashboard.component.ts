import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import{Router} from '@angular/router';
declare let alertify;
@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {
  selectedFile: File;
  selectedFile2: File;
  constructor(private service:DataAccessService,private router : Router) { }

  ngOnInit(): void {
  }
  fromlevel1=[];
   result1=[];
  addlabel1(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    
    this.fromlevel1=[];
      let temp1 = data.value;
    
 
 this.fromlevel1[this.fromlevel1.length] =temp1;
 
      console.log(this.fromlevel1);
  }
  onFileChanged1(event) {
    this.selectedFile = event.target.files[0];
  }
  onFileChanged2(event) {
    this.selectedFile2 = event.target.files[0];
  }
  save(data) {
    const temp = data.value;
    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    } 
    if (this.selectedFile !== undefined) {
      uploadData.append('signature', this.selectedFile, this.selectedFile.name);
    }
    if (this.selectedFile2 !== undefined) {
      uploadData.append('supporting_doc', this.selectedFile2, this.selectedFile2.name);
    }
    this.service.post('qa/all.php?type=saveStanderdOpratingProcedure',uploadData).subscribe(response=>{
      alert("saved succesfully")
      this.router.navigate(['/sales-force/doctor'])
      data.reset();
    });
     
  }

}
