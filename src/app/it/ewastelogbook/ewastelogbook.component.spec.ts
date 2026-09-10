import { ComponentFixture, TestBed } from '@angular/core/testing';

import { EwastelogbookComponent } from './ewastelogbook.component';

describe('EwastelogbookComponent', () => {
  let component: EwastelogbookComponent;
  let fixture: ComponentFixture<EwastelogbookComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ EwastelogbookComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(EwastelogbookComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
