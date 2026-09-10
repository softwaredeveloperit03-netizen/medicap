import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ChangemrphistoryComponent } from './changemrphistory.component';

describe('ChangemrphistoryComponent', () => {
  let component: ChangemrphistoryComponent;
  let fixture: ComponentFixture<ChangemrphistoryComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ChangemrphistoryComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ChangemrphistoryComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
