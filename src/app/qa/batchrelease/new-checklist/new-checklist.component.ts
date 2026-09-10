import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new-checklist',
  templateUrl: './new-checklist.component.html',
  styleUrls: ['./new-checklist.component.css']
})
export class NewChecklistComponent implements OnInit {

  plant_type = 'Formulation';
  fg_api_products;
  dosages;
  products = [];

  checkpoints = [];
   
  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getDosages();
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }

  getDosages() {
    this.service.get('batch-release.php?type=getDosages').subscribe(response => {
      this.dosages = response;
    });
  }

  getProducts(index) {
    index = index - 1;
    this.products = this.dosages[index].products;
  }

  add(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    this.checkpoints[this.checkpoints.length] = temp['checkpoint'];
    data.resetForm();
  }

  del(index) {
    this.checkpoints.splice(index, 1);
  }

  save(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }
    let temp = data.value;
    temp['checkpoints']=this.checkpoints;
    this.service.post('batch-release.php?type=saveChecklist', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Checklist Saved Successfully');
         this.checkpoints = [];
        this.router.navigate(['/batchrelease']);
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }
  get_fg_api_products(){
    this.fg_api_products = []
    this.service.get('master/materialtype.php?type=get_fg_api_products').subscribe(response => {
      this.fg_api_products = response
    })
  }

}
