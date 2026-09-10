import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-env-library',
  templateUrl: './env-library.component.html',
  styleUrls: ['./env-library.component.css']
})
export class EnvLibraryComponent implements OnInit {

  results;
  labours;
  lafs;
  balances;

  constructor(private service : DataAccessService) {
    }

  ngOnInit(): void {
    this.getEnvironment();
    this.getLabours();
  }
  getLabours(){
    this.service.get('common.php?type=getOperators').subscribe(response =>{
      this.labours = response;
    });
  }
 
  getEnvironment(){
    this.service.get('microbiology/environment.php?type=getEnvironment').subscribe(response =>{
      this.results = response;
    });
  }
  
  download(){
    this.service.open('microbiology/environment.php?type=downloadEnvironment')
  }
  save(data){
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('microbiology/environment.php?type=saveEnvironment',JSON.stringify (data.value)).subscribe(response =>{
      if (response['status'] === 'success') {
        this.getEnvironment();
        alertify.success('Record Inserted successfully');
        data.resetForm();
      } else {
        alertify.error(response['status']);
      }
    });
  }
  
}
