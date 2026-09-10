import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MattypeComponent } from './mattype.component';

describe('MattypeComponent', () => {
  let component: MattypeComponent;
  let fixture: ComponentFixture<MattypeComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MattypeComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MattypeComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
