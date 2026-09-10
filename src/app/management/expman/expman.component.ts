import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
@Component({
  selector: 'app-expman',
  templateUrl: './expman.component.html',
  styleUrls: ['./expman.component.css']
})
export class ExpmanComponent implements OnInit {

 
  searchQuery:any;
  materials_data:any;
  constructor(private service: DataAccessService) {}

 ngOnInit() {
    this.getallmaterial();
    this.getexpiredCountAPI();
 }


 getallmaterial() {
   this.service.get('common.php?type=getexpiryManagementData').subscribe(response => {
     this.materials_data = response;
   });
 }

 filterMaterials(months) {
   this.service.get('common.php?type=getMonthWIseData&months='+months).subscribe(response => {
     this.materials_data = response;
   });
 }

 expiredCount = 0;

 getexpiredCountAPI() {
   this.service.get('common.php?type=expiredCountAPI').subscribe(response => {
     this.expiredCount = response['total_expired_count'];
   });
 }


 get filteredMaterials(): any[] {
   if (!this.searchQuery || this.searchQuery.trim() === '') {
     return this.materials_data; // If search query is empty or whitespace, return all materials
   }

   const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace

   return this.materials_data.filter((material) => {
     // Check if any field of the material contains the search query
     return Object.entries(material).some(([key, value]) => {
       return value && value.toString().toLowerCase().includes(query);
     });
   });
 }

 

 matStatus = 'Expired';

 isExpired = false;
 selectedStock = [];

 MakeExpired(index){
   
   this.selectedStock = this.filteredMaterials[index];

   this.isExpired = true;

 }


 distroyMaterial(){

 }


}
