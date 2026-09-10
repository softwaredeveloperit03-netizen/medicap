import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CustomsimportComponent } from './customsimport.component';

describe('CustomsimportComponent', () => {
  let component: CustomsimportComponent;
  let fixture: ComponentFixture<CustomsimportComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CustomsimportComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CustomsimportComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
