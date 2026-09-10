import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-edit',
  templateUrl: './edit.component.html',
  styleUrls: ['./edit.component.css']
})
export class EditComponent implements OnInit {

  isView = false;
  result;

  grades;
  dosages;
  manufacturers;
  clients;
  constructor(private service: DataAccessService, private route:ActivatedRoute, private router: Router) { }

  ngOnInit(): void {
    this.getGrades();
    this.getApprovedClients();
    this.getManufacturers();

    this.route.paramMap.subscribe(params => {
      this.getProductDetails(params.get('id'));
    });
  }

  getProductDetails(id) {
    this.service.get('qa/product.php?type=getProductDetails&id=' + id).subscribe(response => {
      this.result = response;
      this.isView = true;
    });
  }

  getGrades() {
    this.service.get('qa/product.php?type=getGrades').subscribe(response => {
      this.grades = response;
    });
  }

  getDosages(dosage_type) {
    this.service.get('qaDepartment.php?type=getDosagesByType&dosage_type=' + dosage_type).subscribe(response => {
      this.dosages = response;
    });
  }

  getApprovedClients() {
    this.service.get('qaDepartment.php?type=getApprovedClients').subscribe(response => {
      this.clients = response;
    });
  }

  getManufacturers() {
    this.service.get('qaDepartment.php?type=getManufacturers').subscribe(response => {
      this.manufacturers = response;
    });
  }

  update(data) {
    if (!data.valid) {
      alert('All fields are required');
      return;
    }

    let temp = data.value;

    const uploadData = new FormData();
    for (let key in temp) {
      let value = temp[key];
      uploadData.append(key, value);
    }
    uploadData.append("id", this.result["id"]);

    this.service.post('qa/product.php?type=updateProduct', uploadData).subscribe(response => {
      if (response['status'] == 'success') {
        alert('Product updated successfully');
        data.resetForm();
        this.router.navigate(['/product/log']);
      } else {
        alert('Failed: An error occured, please try again!');
      }
    });
  }

}
