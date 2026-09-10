import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { IndendComponent } from './indend.component';

describe('IndendComponent', () => {
  let component: IndendComponent;
  let fixture: ComponentFixture<IndendComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ IndendComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(IndendComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
