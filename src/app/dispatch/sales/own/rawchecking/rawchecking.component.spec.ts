import { ComponentFixture, TestBed } from '@angular/core/testing';

import { RawcheckingComponent } from './rawchecking.component';

describe('RawcheckingComponent', () => {
  let component: RawcheckingComponent;
  let fixture: ComponentFixture<RawcheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ RawcheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(RawcheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
