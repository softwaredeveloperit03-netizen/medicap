import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ImportdetailsComponent } from './importdetails.component';

describe('ImportdetailsComponent', () => {
  let component: ImportdetailsComponent;
  let fixture: ComponentFixture<ImportdetailsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ImportdetailsComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ImportdetailsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
