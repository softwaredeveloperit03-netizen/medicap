import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WebmrComponent } from './webmr.component';

describe('WebmrComponent', () => {
  let component: WebmrComponent;
  let fixture: ComponentFixture<WebmrComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ WebmrComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(WebmrComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
