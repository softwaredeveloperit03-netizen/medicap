import { ComponentFixture, TestBed } from '@angular/core/testing';

import { TicketsLogComponent } from './tickets-log.component';

describe('TicketsLogComponent', () => {
  let component: TicketsLogComponent;
  let fixture: ComponentFixture<TicketsLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ TicketsLogComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(TicketsLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
