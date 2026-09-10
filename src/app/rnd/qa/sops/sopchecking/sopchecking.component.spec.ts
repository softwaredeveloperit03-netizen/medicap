import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SopcheckingComponent } from './sopchecking.component';

describe('SopcheckingComponent', () => {
  let component: SopcheckingComponent;
  let fixture: ComponentFixture<SopcheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SopcheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(SopcheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
