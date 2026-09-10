import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  isView = false;
 

  constructor(private service: DataAccessService, private router: Router) { }
  ngOnInit(): void {
    this.getProducts();
  }
 
  
  results;
  getProducts() {
    this.service.get('master/product.php?type=getProductForApproval').subscribe((response) => {
        this.results = response;
      });
  } 

 selectedProduct =[];
  view(data){
    this.selectedProduct = data;
    this.isView = true;
  }



     
  approveProduct() {

    let temp ={};
    this.service.post('master/product.php?type=approveProduct&id='+this.selectedProduct['id'], JSON.stringify(temp)).subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Product Approved successfully');
          this.isView = false;
          this.getProducts();
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }









  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results; // If search query is empty or whitespace, return all materials
    }

    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

    return this.results.filter((material) => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }






}
