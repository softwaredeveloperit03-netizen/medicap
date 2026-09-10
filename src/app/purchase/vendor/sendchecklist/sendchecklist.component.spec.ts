import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SendchecklistComponent } from './sendchecklist.component';

describe('SendchecklistComponent', () => {
  let component: SendchecklistComponent;
  let fixture: ComponentFixture<SendchecklistComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SendchecklistComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(SendchecklistComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
