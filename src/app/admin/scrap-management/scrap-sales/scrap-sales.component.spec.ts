import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ScrapSalesComponent } from './scrap-sales.component';

describe('ScrapSalesComponent', () => {
  let component: ScrapSalesComponent;
  let fixture: ComponentFixture<ScrapSalesComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ScrapSalesComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ScrapSalesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
