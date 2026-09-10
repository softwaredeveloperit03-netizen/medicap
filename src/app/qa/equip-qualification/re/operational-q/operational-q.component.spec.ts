import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OperationalQComponent } from './operational-q.component';

describe('OperationalQComponent', () => {
  let component: OperationalQComponent;
  let fixture: ComponentFixture<OperationalQComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OperationalQComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OperationalQComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
