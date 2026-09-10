import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import{Router} from '@angular/router';

@Component({
  selector: 'app-plan-dept-head',
  templateUrl: './plan-dept-head.component.html',
  styleUrls: ['./plan-dept-head.component.css']
})
export class PlanDeptHeadComponent implements OnInit {
 

  constructor(private service:DataAccessService,private router : Router) { }

  capa;
  ngOnInit(): void {
    this.getCapa();
  }

  getCapa(){
    this.service.get('qa/all2.php?type=getCapaNo').subscribe((response:any) => {
      this.capa = response;
     
    });
  }
  selectedFile2: File;

  onFileChanged3(event) {
    this.selectedFile2 = event.target.files[0];
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
    
    if (this.selectedFile2 !== undefined) {
      uploadData.append('supporting_doc', this.selectedFile2, this.selectedFile2.name);
    }
  
    this.service.post('qms/capa.php?type=save_plan',uploadData).subscribe(response=>{
      if (response['status'] == 'success') {
        alert('Saved Successfully');
        this.router.navigate(['/qa/capa'])
      } else {
        console.log(response);
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
