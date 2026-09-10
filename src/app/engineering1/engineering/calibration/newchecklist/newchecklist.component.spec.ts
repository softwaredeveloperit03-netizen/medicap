import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NewchecklistComponent } from './newchecklist.component';

describe('NewchecklistComponent', () => {
  let component: NewchecklistComponent;
  let fixture: ComponentFixture<NewchecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NewchecklistComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(NewchecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
