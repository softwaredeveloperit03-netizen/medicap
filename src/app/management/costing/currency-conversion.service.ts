// src/app/services/currency-conversion.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CurrencyConversionService {
  private apiUrl = 'https://api.exchangerate-api.com/v4/latest/INR'; // Replace with your API URL

  constructor(private http: HttpClient) {}

  getConversionRates(): Observable<any> {
    return this.http.get<any>(this.apiUrl);
  }
}
