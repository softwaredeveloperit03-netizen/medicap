import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewcheckComponent } from './newcheck.component';

describe('NewcheckComponent', () => {
  let component: NewcheckComponent;
  let fixture: ComponentFixture<NewcheckComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewcheckComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewcheckComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
