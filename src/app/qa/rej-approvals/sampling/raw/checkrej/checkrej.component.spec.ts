import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CheckrejComponent } from './checkrej.component';

describe('CheckrejComponent', () => {
  let component: CheckrejComponent;
  let fixture: ComponentFixture<CheckrejComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CheckrejComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CheckrejComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
